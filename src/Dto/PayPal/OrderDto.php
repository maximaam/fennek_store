<?php

declare(strict_types=1);

namespace App\Dto\PayPal;

final readonly class OrderDto
{
    public function __construct(
        public string $id,
        public string $status,
        /** @var LinkDto[] */
        public array $links,
    ) {
    }
}
