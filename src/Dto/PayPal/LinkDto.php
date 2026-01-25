<?php

declare(strict_types=1);

namespace App\Dto\PayPal;

final readonly class LinkDto
{
    public function __construct(
        public string $rel,
        public string $href,
        public string $method,
    ) {
    }
}
