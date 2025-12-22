<?php

namespace App\Http\Services;

use App\Enums\Role;
use App\Models\Transfer;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransferService
{
    public function execute(string $payer, string $payee, float $value)
    {

        $payerWallet = Wallet::find($payer);
        $payeeWallet = Wallet::find($payee);

        if (! $payerWallet || ! $payeeWallet) {
            throw new Exception('Wallet not found', code: 422);
        }

        if ($payerWallet->balance < $value) {
            throw new Exception('Insufficient balance', 400);
        }
        if ($payerWallet->user->hasRole(Role::STORE_KEEPER)) {
            throw new Exception('Store keeper cannot transfer funds', 400);
        }
        try {
            DB::transaction(function () use ($payerWallet, $payeeWallet, $value) {

                Transfer::create([
                    'payer_wallet_id' => $payerWallet->id,
                    'payee_wallet_id' => $payeeWallet->id,
                    'value' => $value,
                ]);
                $payerWallet->decrement('balance', $value);
                $payeeWallet->increment('balance', $value);
                $authorized = $this->authorizeTransfer($payerWallet->id, $payeeWallet->id, $value);
                if (! $authorized) {
                    throw new Exception('Transfer not authorized', 403);
                }
                $notified = $this->notifyTransfer($payerWallet->id, $payeeWallet->id, $value);
                if (! $notified) {
                    throw new Exception('Transfer not notified', 400);
                }
            });
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function notifyTransfer(int $payer, int $payee, float $value)
    {
        $http = Http::retry(3, 100); // NOPMD

        // Disable SSL verification in development/local environments
        if (app()->environment(['local', 'development', 'testing'])) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post(config('services.transfer.notify_url'), [
            'payer' => $payer,
            'payee' => $payee,
            'value' => number_format($value, 2, '.', ''),
        ]);

        return $response->successful();
    }

    public function authorizeTransfer(int $payer, int $payee, float $value)
    {
        $http = Http::retry(3, 100); // NOPMD

        // Disable SSL verification in development/local environments
        if (app()->environment(['local', 'development', 'testing'])) {
            $http = $http->withoutVerifying();
        }

        $response = $http->get(config('services.transfer.authorize_url'), [
            'payer' => $payer,
            'payee' => $payee,
            'value' => number_format($value, 2, '.', ''),
        ]);

        return $response->successful();
    }
}
