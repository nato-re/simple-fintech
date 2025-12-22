<?php

namespace App\Http\Services\Contracts;

interface NotificationServiceInterface
{
    /**
     * Notify about a completed transfer.
     *
     * @param  int  $payer Wallet ID of the payer
     * @param  int  $payee Wallet ID of the payee
     * @param  float  $value Transfer amount
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function notify(int $payer, int $payee, float $value): bool;
}

