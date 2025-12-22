<?php

namespace App\Http\Services;

use App\Enums\Role;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\StoreKeeperTransferException;
use App\Exceptions\TransferException;
use App\Exceptions\TransferNotAuthorizedException;
use App\Exceptions\TransferNotificationFailedException;
use App\Exceptions\WalletNotFoundException;
use App\Http\Services\Contracts\AuthorizationServiceInterface;
use App\Http\Services\Contracts\NotificationServiceInterface;
use App\Repositories\Contracts\TransferRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransferRepositoryInterface $transferRepository,
        private AuthorizationServiceInterface $authorizationService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function execute(string $payer, string $payee, float $value): void
    {
        $payerWallet = $this->walletRepository->findByIdWithUser((int) $payer);
        $payeeWallet = $this->walletRepository->findById((int) $payee);

        if (! $payerWallet) {
            throw new WalletNotFoundException($payer, [
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value,
            ]);
        }

        if (! $payeeWallet) {
            throw new WalletNotFoundException($payee, [
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value,
            ]);
        }

        if (! $this->walletRepository->hasSufficientBalance($payerWallet->id, $value)) {
            throw new InsufficientBalanceException(
                $payerWallet->balance,
                $value,
                [
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]
            );
        }

        if ($payerWallet->user->hasRole(Role::STORE_KEEPER)) {
            throw new StoreKeeperTransferException([
                'payer_wallet_id' => $payerWallet->id,
                'payer_user_id' => $payerWallet->user->id,
                'payee_wallet_id' => $payeeWallet->id,
                'value' => $value,
            ]);
        }

        try {
            DB::transaction(function () use ($payerWallet, $payeeWallet, $value) {
                $this->transferRepository->create([
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]);

                $this->walletRepository->decrementBalance($payerWallet->id, $value);
                $this->walletRepository->incrementBalance($payeeWallet->id, $value);

                $authorized = $this->authorizationService->authorize($payerWallet->id, $payeeWallet->id, $value);
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

                $notified = $this->notificationService->notify($payerWallet->id, $payeeWallet->id, $value);
                if (! $notified) {
                    Log::error('Transfer notification failed', [
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);

                    throw new TransferNotificationFailedException([
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);
                }

                Log::info('Transfer completed successfully', [
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]);
            });
        } catch (\App\Exceptions\BaseException $e) {
            // Re-throw custom exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Wrap unexpected exceptions
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
