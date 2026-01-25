<?php

declare(strict_types=1);

namespace App\Dto\PayPal;

final readonly class OrderCaptureDto
{
    public function __construct(
        public string $id,
        public array $links,
        public array $payer,
        public string $status,
        public array $payment_source, // snake_case to keep paypal's object naming
        public array $purchase_units,
    ) {
    }
}
