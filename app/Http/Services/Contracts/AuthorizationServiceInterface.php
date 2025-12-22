<?php

namespace App\Http\Services\Contracts;

interface AuthorizationServiceInterface
{
    /**
     * Authorize a transfer between wallets.
     *
     * @param  int  $payer Wallet ID of the payer
     * @param  int  $payee Wallet ID of the payee
     * @param  float  $value Transfer amount
     * @return bool True if authorized, false otherwise
     */
    public function authorize(int $payer, int $payee, float $value): bool;
}

