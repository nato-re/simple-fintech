<?php

namespace App\Enums;

enum Role: string
{
    case CUSTOMER = 'customer';
    case STORE_KEEPER = 'store_keeper';

    /**
     * Get all role values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
