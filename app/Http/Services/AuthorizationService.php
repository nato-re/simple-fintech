<?php

namespace App\Http\Services;

use App\Http\Services\Contracts\AuthorizationServiceInterface;
use App\ValueObjects\Money;

class AuthorizationService extends AbstractHttpService implements AuthorizationServiceInterface
{
    /**
     * Authorize a transfer between wallets.
     *
     * @param  int  $payer  Wallet ID of the payer
     * @param  int  $payee  Wallet ID of the payee
     * @param  Money  $value  Transfer amount
     * @return bool True if authorized, false otherwise
     */
    public function authorize(int $payer, int $payee, Money $value): bool
    {
        try {
            $response = $this->getHttpClient()->get(config('services.transfer.authorize_url'), [
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value->toFloat(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return $this->handleHttpException($e, [
                'service' => 'authorization',
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value->toFloat(),
            ]);
        }
    }
}
