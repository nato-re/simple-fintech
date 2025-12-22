<?php

namespace App\Http\Services;

use App\Enums\Role;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\StoreKeeperTransferException;
use App\Exceptions\WalletNotFoundException;
use App\Models\Wallet;
use App\ValueObjects\Money;

/**
 * Service responsible for validating transfer business rules.
 *
 * This class encapsulates all validation logic for transfers,
 * following the Single Responsibility Principle.
 */
class TransferValidator
{
    public function __construct() {}

    /**
     * Validate all transfer business rules.
     *
     * @param  Wallet|null  $payerWallet
     * @param  Wallet|null  $payeeWallet
     * @param  Money  $transferValue
     * @param  string  $payerId Original payer ID for error context
     * @param  string  $payeeId Original payee ID for error context
     * @param  float  $originalValue Original float value for error context
     * @return void
     * @throws WalletNotFoundException
     * @throws InsufficientBalanceException
     * @throws StoreKeeperTransferException
     */
    public function validate(
        ?Wallet $payerWallet,
        ?Wallet $payeeWallet,
        Money $transferValue,
        string $payerId,
        string $payeeId,
    ): void {
        $this->validateWalletsExist($payerWallet, $payeeWallet, $payerId, $payeeId, $transferValue);
        $this->validateSufficientBalance($payerWallet, $transferValue, $payeeWallet);
        $this->validatePayerIsNotStoreKeeper($payerWallet, $payeeWallet, $transferValue);
    }

    /**
     * Validate that both wallets exist.
     *
     * @param  Wallet|null  $payerWallet
     * @param  Wallet|null  $payeeWallet
     * @param  string  $payerId
     * @param  string  $payeeId
     * @param  Money  $transferValue
     * @return void
     * @throws WalletNotFoundException
     */
    private function validateWalletsExist(
        ?Wallet $payerWallet,
        ?Wallet $payeeWallet,
        string $payerId,
        string $payeeId,
        Money $transferValue
    ): void {
        if (! $payerWallet) {
            throw new WalletNotFoundException($payerId, [
                'payer' => $payerId,
                'payee' => $payeeId,
                'value' => $transferValue->toFloat(),
            ]);
        }

        if (! $payeeWallet) {
            throw new WalletNotFoundException($payeeId, [
                'payer' => $payerId,
                'payee' => $payeeId,
                'value' => $transferValue->toFloat(),
            ]);
        }
    }

    /**
     * Validate that payer has sufficient balance.
     *
     * @param  Wallet  $payerWallet
     * @param  Money  $transferValue
     * @param  Wallet  $payeeWallet
     * @param  Money  $transferValue
     * @return void
     * @throws InsufficientBalanceException
     */
    private function validateSufficientBalance(
        Wallet $payerWallet,
        Money $transferValue,
        Wallet $payeeWallet
    ): void {
        $payerBalance = Money::fromFloat($payerWallet->balance);

        if ($payerBalance->isLessThan($transferValue)) {
            throw new InsufficientBalanceException(
                $payerWallet->balance,
                $transferValue->toFloat(),
                [
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $transferValue->toFloat(),
                ]
            );
        }
    }

    /**
     * Validate that payer is not a store keeper.
     *
     * @param  Wallet  $payerWallet
     * @param  Wallet  $payeeWallet
     * @param  Money  $transferValue
     * @return void
     * @throws StoreKeeperTransferException
     */
    private function validatePayerIsNotStoreKeeper(
        Wallet $payerWallet,
        Wallet $payeeWallet,
        Money $transferValue
    ): void {
        if ($payerWallet->user->hasRole(Role::STORE_KEEPER)) {
            throw new StoreKeeperTransferException([
                'payer_wallet_id' => $payerWallet->id,
                'payer_user_id' => $payerWallet->user->id,
                'payee_wallet_id' => $payeeWallet->id,
                'value' => $transferValue->toFloat(),
            ]);
        }
    }

    /**
     * Validate balance after lock (re-check for race conditions).
     *
     * @param  Wallet  $lockedPayerWallet
     * @param  Money  $transferValue
     * @param  Wallet  $lockedPayeeWallet
     * @param  Money  $transferValue
     * @return void
     * @throws InsufficientBalanceException
     */
    public function validateBalanceAfterLock(
        Wallet $lockedPayerWallet,
        Money $transferValue,
        Wallet $lockedPayeeWallet
    ): void {
        $lockedPayerBalance = Money::fromFloat($lockedPayerWallet->balance);

        if ($lockedPayerBalance->isLessThan(other: $transferValue)) {
            throw new InsufficientBalanceException(
                $lockedPayerWallet->balance,
                $transferValue->toFloat(),
                [
                    'payer_wallet_id' => $lockedPayerWallet->id,
                    'payee_wallet_id' => $lockedPayeeWallet->id,
                    'value' => $transferValue->toFloat(),
                ]
            );
        }
    }
}

