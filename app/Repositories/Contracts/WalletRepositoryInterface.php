<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryInterface
{
    /**
     * Find a wallet by ID.
     */
    public function findById(int $walletId): ?Wallet;

    /**
     * Find a wallet by ID with user relationship.
     */
    public function findByIdWithUser(int $walletId): ?Wallet;

    /**
     * Update wallet balance.
     */
    public function updateBalance(int $walletId, float $amount): void;

    /**
     * Decrement wallet balance.
     */
    public function decrementBalance(int $walletId, float $amount): void;

    /**
     * Increment wallet balance.
     */
    public function incrementBalance(int $walletId, float $amount): void;

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(int $walletId, float $amount): bool;

    /**
     * Lock wallet for update (pessimistic locking to prevent race conditions).
     */
    public function lockForUpdate(int $walletId): ?Wallet;
}

