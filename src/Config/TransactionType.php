<?php

namespace App\Config;

use App\Exception\TypeNotFoundException;

class TransactionType
{
    public const DEPOSIT = 0;
    public const PAYMENT = 1;

    public static function typeToString(int $type): string
    {
        return match ($type) {
            0 => 'deposit',
            1 => 'payment',
            default => throw new TypeNotFoundException("Тип транзакции $type не найден"),
        };
    }

    public static function stringToType(string $str): int
    {
        return match ($str) {
            'deposit' => 0,
            'payment' => 1,
            default => throw new TypeNotFoundException("Тип транзакции $str не существует"),
        };
    }
}