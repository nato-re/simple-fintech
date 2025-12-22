<?php

namespace App\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode;

    /**
     * Internal error code for this exception.
     */
    protected string $errorCode;

    /**
     * Additional context data for logging.
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $statusCode = 500,
        string $errorCode = 'INTERNAL_ERROR',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the internal error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert the exception to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getErrorCode(),
            'errors' => [],
        ];
    }
}
