<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Product;

final readonly class CatalogueResultDto
{
    /**
     * @param Product[]|null $products
     */
    public function __construct(
        public array $category,
        public array $subCategories,
        public ?array $products,
        public ?array $product,
        public string $template,
    ) {
    }
}
