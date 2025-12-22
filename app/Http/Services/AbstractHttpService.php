<?php

namespace App\Http\Services;

use App\Constants\TransferConstants;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractHttpService
{
    /**
     * Get configured HTTP client with retry and SSL settings.
     */
    protected function getHttpClient(): PendingRequest
    {
        $http = Http::retry(
            TransferConstants::HTTP_RETRY_ATTEMPTS,
            TransferConstants::HTTP_RETRY_DELAY_MS
        );

        // Disable SSL verification in development/local environments
        if (app()->environment(['local', 'development', 'testing'])) {
            $http = $http->withoutVerifying();
        }

        return $http;
    }

    /**
     * Handle HTTP exceptions with appropriate logging.
     *
     * @param  array<string, mixed>  $context
     * @return bool Always returns false (indicates failure)
     */
    protected function handleHttpException(\Throwable $exception, array $context): bool
    {
        if ($exception instanceof ConnectionException) {
            // Network errors: timeout, DNS failure, connection refused
            Log::warning('Network error during HTTP request', array_merge($context, [
                'error' => $exception->getMessage(),
            ]));

            return false;
        }

        // Other unexpected errors
        Log::error('Unexpected error during HTTP request', array_merge($context, [
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]));

        return false;
    }

    /**
     * Format monetary value for API requests.
     */
    protected function formatValue(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
