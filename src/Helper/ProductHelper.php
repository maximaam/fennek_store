<?php

declare(strict_types=1);

namespace App\Helper;

final readonly class ProductHelper
{
    public const int VAT = 19;

    /**
     * @param array<int, mixed> $cart
     *
     * @return array<int|string, mixed>
     */
    public static function computeCard(array $cart): array
    {
        $data = $result = [];
        $total = 0;
        foreach ($cart as $itemKey => $item) {
            $total += (int) $item['full_price'];
            $data[$itemKey] = $item;
            $exclTax = round($total / ((self::VAT / 100) + 1), 2);
            $result = [
                'products' => $data,
                'totals' => [
                    'excl_tax' => $exclTax,
                    'vat' => round($total - $exclTax, 2),
                    'total' => $total,
                ],
            ];
        }

        return $result;
    }

    public static function formatCartToPayPalOrder(array $cart): array
    {
        foreach ($cart as $itemKey => $item) {

        }
    }
}
