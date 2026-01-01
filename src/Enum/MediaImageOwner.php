<?php

declare(strict_types=1);

namespace App\Enum;

enum MediaImageOwner: string
{
    case PRODUCT = 'product';
    case PAGE = 'page';
}
