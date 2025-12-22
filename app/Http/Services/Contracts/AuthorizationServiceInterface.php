<?php

namespace App\Http\Services\Contracts;

use App\ValueObjects\Money;

interface AuthorizationServiceInterface
{
    /**
     * Authorize a transfer between wallets.
     *
     * @param  int  $payer  Wallet ID of the payer
     * @param  int  $payee  Wallet ID of the payee
     * @param  Money  $value  Transfer amount
     * @return bool True if authorized, false otherwise
     */
    public function authorize(int $payer, int $payee, Money $value): bool;
}
