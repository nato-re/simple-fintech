<?php

namespace App\Http\Services;

use App\Http\Services\Contracts\AuthorizationServiceInterface;
use Illuminate\Support\Facades\Log;

class AuthorizationService extends AbstractHttpService implements AuthorizationServiceInterface
{
    /**
     * Authorize a transfer between wallets.
     *
     * @param  int  $payer Wallet ID of the payer
     * @param  int  $payee Wallet ID of the payee
     * @param  float  $value Transfer amount
     * @return bool True if authorized, false otherwise
     */
    public function authorize(int $payer, int $payee, float $value): bool
    {
        try {
            $response = $this->getHttpClient()->get(config('services.transfer.authorize_url'), [
                'payer' => $payer,
                'payee' => $payee,
                'value' => $this->formatValue($value),
            ]);

            // HTTP Client doesn't throw exceptions for 4xx/5xx responses
            // We need to check if the response is successful (200-299)
            return $response->successful();
        } catch (\Throwable $e) {
            return $this->handleHttpException($e, [
                'service' => 'authorization',
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value,
            ]);
        }
    }
}

