<?php

namespace App\Exceptions;

class TransferNotAuthorizedException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Transfer not authorized',
            statusCode: 403,
            errorCode: 'TRANSFER_NOT_AUTHORIZED',
            context: $context
        );
    }
}

