<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Category;

final readonly class CategoryViewDto
{
    public function __construct(
        public ?int $id,
        public ?\DateTimeImmutable $updatedAt,
        public string $name,
        public string $alias,
        public ?string $description,
        public ?int $parentId,
    ) {
    }

    public static function fromCategory(Category $category, string $locale): self
    {
        $translation = $category->getTranslationByLocale($locale);

        return new self(
            id: $category->getId(),
            updatedAt: $category->getUpdatedAt(),
            name: $translation->getName(),
            alias: $translation->getAlias(),
            description: $translation->getDescription(),
            parentId: $category->getParent()?->getId() ?? null,
        );
    }
}
