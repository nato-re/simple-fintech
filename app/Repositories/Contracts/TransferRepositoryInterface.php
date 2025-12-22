<?php

namespace App\Repositories\Contracts;

use App\Models\Transfer;

interface TransferRepositoryInterface
{
    /**
     * Create a new transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Transfer;

    /**
     * Find a transfer by ID.
     */
    public function findById(int $transferId): ?Transfer;

    /**
     * Get all transfers for a wallet (as payer).
     */
    public function getByPayerWalletId(int $walletId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get all transfers for a wallet (as payee).
     */
    public function getByPayeeWalletId(int $walletId): \Illuminate\Database\Eloquent\Collection;
}
