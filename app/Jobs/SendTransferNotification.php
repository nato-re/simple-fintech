<?php

namespace App\Jobs;

use App\Http\Services\Contracts\NotificationServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendTransferNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Payer wallet ID.
     *
     * @var int
     */
    public int $payerWalletId;

    /**
     * Payee wallet ID.
     *
     * @var int
     */
    public int $payeeWalletId;

    /**
     * Transfer value.
     *
     * @var float
     */
    public float $value;

    /**
     * Create a new job instance.
     *
     * @param  int  $payerWalletId
     * @param  int  $payeeWalletId
     * @param  float  $value
     */
    public function __construct(int $payerWalletId, int $payeeWalletId, float $value)
    {
        $this->payerWalletId = $payerWalletId;
        $this->payeeWalletId = $payeeWalletId;
        $this->value = $value;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        Log::info('Processing transfer notification job', [
            'payer_wallet_id' => $this->payerWalletId,
            'payee_wallet_id' => $this->payeeWalletId,
            'value' => $this->value,
            'attempt' => $this->attempts(),
        ]);

        $notified = $notificationService->notify(
            $this->payerWalletId,
            $this->payeeWalletId,
            $this->value
        );

        if (! $notified) {
            Log::warning('Transfer notification failed in job', [
                'payer_wallet_id' => $this->payerWalletId,
                'payee_wallet_id' => $this->payeeWalletId,
                'value' => $this->value,
                'attempt' => $this->attempts(),
            ]);

            // Throw exception to trigger retry
            throw new RuntimeException('Transfer notification failed');
        }

        Log::info('Transfer notification sent successfully', [
            'payer_wallet_id' => $this->payerWalletId,
            'payee_wallet_id' => $this->payeeWalletId,
            'value' => $this->value,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Transfer notification job failed after all retries', [
            'payer_wallet_id' => $this->payerWalletId,
            'payee_wallet_id' => $this->payeeWalletId,
            'value' => $this->value,
            'attempts' => $this->attempts(),
            'exception' => $exception ? get_class($exception) : null,
            'error' => $exception?->getMessage(),
        ]);

        // TODO: Implement dead letter queue or alert system
        // Could send to monitoring service, create alert, etc.
    }
}

