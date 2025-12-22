<?php

namespace App\Exceptions;

class InsufficientBalanceException extends BaseException
{
    /**
     * Create a new exception instance.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(float $currentBalance, float $requiredAmount, array $context = [])
    {
        $message = sprintf(
            'Insufficient balance. Available: %s, Required: %s',
            number_format($currentBalance, 2, '.', ''),
            number_format($requiredAmount, 2, '.', '')
        );

        parent::__construct(
            message: $message,
            statusCode: 400,
            errorCode: 'INSUFFICIENT_BALANCE',
            context: array_merge($context, [
                'current_balance' => $currentBalance,
                'required_amount' => $requiredAmount,
            ])
        );
    }
}
