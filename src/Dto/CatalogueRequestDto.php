<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CatalogueRequestDto
{
    public function __construct(
        public string $locale,
        public string $categoryAlias,
        public ?string $subCategoryAlias,
        public ?string $productAlias,
    ) {
    }

    public function isProductView(): bool
    {
        return null !== $this->productAlias;
    }

    public function isMainCategoryView(): bool
    {
        return null === $this->productAlias && null === $this->subCategoryAlias;
    }

    public function isSubCategoryView(): bool
    {
        return null === $this->productAlias && null !== $this->subCategoryAlias;
    }
}
