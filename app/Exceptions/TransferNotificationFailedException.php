<?php

namespace App\Exceptions;

class TransferNotificationFailedException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Transfer notification failed',
            statusCode: 400,
            errorCode: 'TRANSFER_NOTIFICATION_FAILED',
            context: $context
        );
    }
}
