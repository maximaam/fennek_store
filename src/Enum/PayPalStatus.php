<?php

declare(strict_types=1);

namespace App\Enum;

enum PayPalStatus: string
{
    case CREATED = 'CREATED';
    // case APPROVED = 'APPROVED';
    case COMPLETED = 'COMPLETED';
    // case DECLINED = 'DECLINED';
    // case EXPIRED = 'EXPIRED';
    // case CANCELLED = 'CANCELLED';
    // case REFUNDED = 'REFUNDED';
    // case PENDING = 'PENDING';
    // case PROCESSING = 'PROCESSING';
    // case REVERSED = 'REVERSED';
    // case REVERSED_PENDING = 'REVERSED_PENDING';

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
