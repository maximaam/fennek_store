<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CatalogueRequestDto
{
    public function __construct(
        public string $locale,
        public string $category,
        public ?string $productSlug,
    ) {
    }

    public function isProductView(): bool
    {
        return null !== $this->productSlug;
    }

    public function isCategoryView(): bool
    {
        return null === $this->productSlug;
    }
}
