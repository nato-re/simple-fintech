<?php

namespace App\Http\Services;

use App\Http\Services\Contracts\NotificationServiceInterface;

class NotificationService extends AbstractHttpService implements NotificationServiceInterface
{
    /**
     * Notify about a completed transfer.
     *
     * @param  int  $payer  Wallet ID of the payer
     * @param  int  $payee  Wallet ID of the payee
     * @param  float  $value  Transfer amount
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function notify(int $payer, int $payee, float $value): bool
    {
        try {
            $response = $this->getHttpClient()->post(config('services.transfer.notify_url'), [
                'payer' => $payer,
                'payee' => $payee,
                'value' => $this->formatValue($value),
            ]);

            // HTTP Client doesn't throw exceptions for 4xx/5xx responses
            // We need to check if the response is successful (200-299)
            return $response->successful();
        } catch (\Throwable $e) {
            return $this->handleHttpException($e, [
                'service' => 'notification',
                'payer' => $payer,
                'payee' => $payee,
                'value' => $value,
            ]);
        }
    }
}
