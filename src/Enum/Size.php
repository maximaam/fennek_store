<?php

declare(strict_types=1);

namespace App\Enum;

enum Size: string
{
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $element) => $element->value,
            self::cases()
        );
    }

    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
