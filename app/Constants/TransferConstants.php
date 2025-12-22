<?php

namespace App\Constants;

class TransferConstants
{
    /**
     * Minimum transfer value in BRL.
     */
    public const MIN_VALUE = 0.01;

    /**
     * Maximum transfer value in BRL.
     */
    public const MAX_VALUE = 999999999.99;

    /**
     * HTTP retry attempts for external services.
     */
    public const HTTP_RETRY_ATTEMPTS = 3;

    /**
     * HTTP retry delay in milliseconds.
     */
    public const HTTP_RETRY_DELAY_MS = 100;

    /**
     * Cache TTL for wallet queries (in seconds).
     * Default: 1 hour (3600 seconds).
     */
    public const CACHE_TTL_WALLET = 3600;

    /**
     * Cache TTL for wallet queries with user relationship (in seconds).
     * Default: 1 hour (3600 seconds).
     */
    public const CACHE_TTL_WALLET_WITH_USER = 3600;
}

