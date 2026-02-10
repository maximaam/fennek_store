<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CatalogueRequestDto
{
    public function __construct(
        public string $locale,
        public string $categoryAlias,
        public ?string $subCategoryAlias,
        public ?int $productId,
        public ?string $productAlias,
    ) {
    }

    public function isProductView(): bool
    {
        return null !== $this->productId;
    }

    public function isMainCategoryView(): bool
    {
        return null === $this->productId && null === $this->subCategoryAlias;
    }

    public function isSubCategoryView(): bool
    {
        return null === $this->productId && null !== $this->subCategoryAlias;
    }
}
