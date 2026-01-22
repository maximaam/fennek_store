<?php

declare(strict_types=1);

namespace App\Helper;

final readonly class PurchaseEaCrudHelper
{
    public static function getPayerEmail(array $payload): string
    {
        return $payload['payer']['email_address'];
    }

    public static function getPayerName(array $payload): string
    {
        return \sprintf(
            '%s %s',
            $payload['payer']['name']['given_name'],
            $payload['payer']['name']['surname'],
        );
    }

    public static function getAmount(array $payload): string
    {
        $amount = $payload['purchase_units'][0]['payments']['captures'][0]['amount'];

        return \sprintf('%s %s', $amount['value'], $amount['currency_code']);
    }
}
