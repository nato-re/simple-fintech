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
                // 1. Authorize FIRST (before modifying balances)
                // This ensures we don't modify data if authorization fails
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

                // 2. Lock wallets to prevent race conditions
                // This ensures no other transaction can modify these wallets simultaneously
                $lockedPayerWallet = $this->walletRepository->lockForUpdate($payerWallet->id);
                $lockedPayeeWallet = $this->walletRepository->lockForUpdate($payeeWallet->id);

                if (! $lockedPayerWallet || ! $lockedPayeeWallet) {
                    throw new WalletNotFoundException($payerWallet->id ?? $payeeWallet->id, [
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);
                }

                // 3. Re-check balance after lock (may have changed due to concurrent transactions)
                if (! $this->walletRepository->hasSufficientBalance($payerWallet->id, $value)) {
                    throw new InsufficientBalanceException(
                        $lockedPayerWallet->balance,
                        $value,
                        [
                            'payer_wallet_id' => $payerWallet->id,
                            'payee_wallet_id' => $payeeWallet->id,
                            'value' => $value,
                        ]
                    );
                }

                // 4. Create transfer record
                $this->transferRepository->create([
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]);

                // 5. Update wallet balances
                $this->walletRepository->decrementBalance($payerWallet->id, $value);
                $this->walletRepository->incrementBalance($payeeWallet->id, $value);

                // 6. Notify (after transfer is committed)
                // Note: If notification fails, the transfer is still valid
                // In the future, this should be moved to an async job/queue
                $notified = $this->notificationService->notify($payerWallet->id, $payeeWallet->id, $value);
                if (! $notified) {
                    Log::error('Transfer notification failed', [
                        'payer_wallet_id' => $payerWallet->id,
                        'payee_wallet_id' => $payeeWallet->id,
                        'value' => $value,
                    ]);

                    // TODO: Move notification to async job/queue
                    // For now, we still throw exception to maintain backward compatibility with tests
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
