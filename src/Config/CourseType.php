<?php

namespace App\Config;

use App\Exception\TypeNotFoundException;

class CourseType
{
    public const FREE = 0;
    public const RENT = 1;
    public const BUY = 2;

    public static function typeToString(int $type): string
    {
        return match ($type) {
            0 => 'free',
            1 => 'rent',
            2 => 'buy',
            default => throw new TypeNotFoundException("Тип курса $type не найден"),
        };
    }

    public static function stringToType(string $type): int
    {
        return match ($type) {
            'free' => 0,
            'rent' => 1,
            'buy' => 2,
            default => throw new TypeNotFoundException("Тип курса $type не найден"),
        };
    }
}