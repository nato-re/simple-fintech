<?php

namespace App\Exceptions;

class TransferException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message = 'Transfer failed', array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            statusCode: 500,
            errorCode: 'TRANSFER_ERROR',
            context: $context,
            previous: $previous
        );
    }
}
