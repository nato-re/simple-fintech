<?php

namespace App\Repositories;

use App\Constants\TransferConstants;
use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(private Wallet $model) {}

    /**
     * Find a wallet by ID.
     */
    public function findById(int $walletId): ?Wallet
    {
        return Cache::remember("wallet.{$walletId}", TransferConstants::CACHE_TTL_WALLET, function () use ($walletId) {
            return $this->model->find($walletId);
        });
    }

    /**
     * Find a wallet by ID with user relationship.
     */
    public function findByIdWithUser(int $walletId): ?Wallet
    {
        return Cache::remember("wallet.{$walletId}.with.user", TransferConstants::CACHE_TTL_WALLET_WITH_USER, function () use ($walletId) {
            return $this->model->with('user')->find($walletId);
        });
    }

    /**
     * Update wallet balance.
     */
    public function updateBalance(int $walletId, float $amount): void
    {
        $this->model->where('id', $walletId)->update(['balance' => $amount]);
        $this->clearCache($walletId);
    }

    /**
     * Decrement wallet balance.
     */
    public function decrementBalance(int $walletId, float $amount): void
    {
        $this->model->where('id', $walletId)->decrement('balance', $amount);
        $this->clearCache($walletId);
    }

    /**
     * Increment wallet balance.
     */
    public function incrementBalance(int $walletId, float $amount): void
    {
        $this->model->where('id', $walletId)->increment('balance', $amount);
        $this->clearCache($walletId);
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(int $walletId, float $amount): bool
    {
        $wallet = $this->findById($walletId);

        return $wallet !== null && $wallet->balance >= $amount;
    }

    /**
     * Lock wallet for update (pessimistic locking to prevent race conditions).
     */
    public function lockForUpdate(int $walletId): ?Wallet
    {
        return $this->model->where('id', $walletId)->lockForUpdate()->first();
    }

    /**
     * Clear cache for a wallet.
     */
    private function clearCache(int $walletId): void
    {
        Cache::forget("wallet.{$walletId}");
        Cache::forget("wallet.{$walletId}.with.user");
    }
}

