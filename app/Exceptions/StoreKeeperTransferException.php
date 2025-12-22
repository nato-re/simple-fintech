<?php

namespace App\Exceptions;

class StoreKeeperTransferException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Store keeper cannot transfer funds',
            statusCode: 400,
            errorCode: 'STORE_KEEPER_TRANSFER_FORBIDDEN',
            context: $context
        );
    }
}
