<?php

declare(strict_types=1);

namespace App\Enum;

enum Color: string
{
    case WHITE = 'white';
    case SILVER = 'silver';
    case GRAY = 'gray';
    case BLACK = 'black';

    case BEIGE = 'beige';
    case YELLOW = 'yellow';
    case GOLD = 'gold';
    case ORANGE = 'orange';
    case RED = 'red';

    case PINK = 'pink';
    case VIOLET = 'violet';
    case FUCHSIA = 'fuchsia';
    case PURPLE = 'purple';

    case LIGHTBLUE = 'lightblue';
    case BLUE = 'blue';
    case DARKBLUE = 'darkblue';

    case GREEN = 'green';
    case LIGHTGREEN = 'lightgreen';

    case BURLYWOOD = 'burlywood';
    case BROWN = 'brown';
    case MAROON = 'maroon';
    case DARKRED = 'darkred';

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
