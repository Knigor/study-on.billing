<?php

namespace App\Enum;

enum EnumCourseType: string
{
    case RENT = 'rent';
    case FREE = 'free';
    case BUY = 'buy';

    public function code(): int
    {
        return match ($this) {
            self::RENT => 1,
            self::FREE => 0,
            self::BUY => 2,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::RENT => 'Аренда',
            self::FREE => 'Бесплатный',
            self::BUY => 'Платный',
        };
    }

    public static function byString(string $value): EnumCourseType
    {
        return match ($value) {
            'rent' => self::RENT,
            'free' => self::FREE,
            'buy' => self::BUY,
        };
    }

    public static function byCode(int $code): EnumCourseType
    {
        return match ($code) {
            1 => self::RENT,
            0 => self::FREE,
            2 => self::BUY,
        };
    }
}