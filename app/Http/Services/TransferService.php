<?php

namespace App\Http\Services;

use App\Exceptions\TransferException;
use App\Exceptions\TransferNotAuthorizedException;
use App\Exceptions\WalletNotFoundException;
use App\Http\Services\Contracts\AuthorizationServiceInterface;
use App\Jobs\SendTransferNotification;
use App\Repositories\Contracts\TransferRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransferRepositoryInterface $transferRepository,
        private AuthorizationServiceInterface $authorizationService,
        private TransferValidator $validator
    ) {}

    public function execute(string $payer, string $payee, float $value): void
    {
        $transferValue = Money::fromFloat($value);

        $payerWallet = $this->walletRepository->findByIdWithUser((int) $payer);
        $payeeWallet = $this->walletRepository->findById((int) $payee);

        $this->validator->validate($payerWallet, $payeeWallet, $transferValue, $payer, $payee);

        try {
            DB::transaction(function () use ($payerWallet, $payeeWallet, $transferValue, $value) {
                $authorized = $this->authorizationService->authorize(
                    $payerWallet->id,
                    $payeeWallet->id,
                    $transferValue
                );
                if (! $authorized) {
                    Log::warning('Transfer not authorized by third party', [
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);

                    throw new TransferNotAuthorizedException([
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);
                }

                $lockedPayerWallet = $this->walletRepository->lockForUpdate($payerWallet->id);
                $lockedPayeeWallet = $this->walletRepository->lockForUpdate($payeeWallet->id);

                if (! $lockedPayerWallet || ! $lockedPayeeWallet) {
                    throw new WalletNotFoundException($payerWallet->id ?? $payeeWallet->id, [
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);
                }

                $this->validator->validateBalanceAfterLock(
                    $lockedPayerWallet,
                    $transferValue,
                    $payeeWallet
                );

                $this->transferRepository->create([
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $transferValue->toFloat(),
                ]);

                $this->walletRepository->decrementBalance($payerWallet->id, $transferValue->toFloat());
                $this->walletRepository->incrementBalance($payeeWallet->id, $transferValue->toFloat());

                Log::info('Transfer completed successfully', [
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]);
            });

            SendTransferNotification::dispatch($payerWallet->id, $payeeWallet->id, $transferValue->toFloat());

            Log::info('Transfer notification job dispatched', [
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'value' => $value,
            ]);
        } catch (\App\Exceptions\BaseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error during transfer', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value,
                'trace' => $e->getTraceAsString(),
            ]);

            throw new TransferException(
                'An unexpected error occurred during the transfer',
                [
                    'payer' => $payer,
                    'payee' => $payee,
                    'value' => $value,
                ],
                $e
            );
        }
    }
}
