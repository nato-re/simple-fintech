<?php

namespace App\Repositories;

use App\Models\Transfer;
use App\Repositories\Contracts\TransferRepositoryInterface;

class TransferRepository implements TransferRepositoryInterface
{
    public function __construct(private Transfer $model) {}

    /**
     * Create a new transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Transfer
    {
        return $this->model->create($data);
    }

    /**
     * Find a transfer by ID.
     */
    public function findById(int $transferId): ?Transfer
    {
        return $this->model->find($transferId);
    }

    /**
     * Get all transfers for a wallet (as payer).
     */
    public function getByPayerWalletId(int $walletId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('payer_wallet_id', $walletId)->get();
    }

    /**
     * Get all transfers for a wallet (as payee).
     */
    public function getByPayeeWalletId(int $walletId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('payee_wallet_id', $walletId)->get();
    }
}

