<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Product;

final readonly class CatalogueResultDto
{
    /**
     * @param Product[]|null $products
     */
    public function __construct(
        public ?Category $category,
        public ?array $products,
        public ?Product $product,
        public string $template,
    ) {
    }
}
