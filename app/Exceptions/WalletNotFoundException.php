<?php

namespace App\Exceptions;

class WalletNotFoundException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(int|string $walletId, array $context = [])
    {
        parent::__construct(
            message: 'Wallet not found',
            statusCode: 404,
            errorCode: 'WALLET_NOT_FOUND',
            context: array_merge($context, ['wallet_id' => $walletId])
        );
    }
}
