<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PayPal\LinkDto;
use App\Dto\PayPal\OrderCaptureDto;
use App\Dto\PayPal\OrderDto;

final class PayPalMapper
{
    /** @param array<string, mixed> $data */
    public static function mapOrder(array $data): OrderDto
    {
        return new OrderDto(
            id: $data['id'],
            status: $data['status'],
            links: array_map(
                static fn (array $link) => new LinkDto(
                    rel: $link['rel'],
                    href: $link['href'],
                    method: $link['method'],
                ),
                $data['links'] ?? []
            ),
        );
    }

    /** @param array<string, mixed> $data */
    public static function mapCapture(array $data): OrderCaptureDto
    {
        return new OrderCaptureDto(
            id: $data['id'],
            links: $data['links'],
            payer: $data['payer'],
            status: $data['status'],
            payment_source: $data['payment_source'],
            purchase_units: $data['purchase_units'],
        );
    }
}
