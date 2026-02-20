<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Category;

final readonly class CategoryViewDto
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $updatedAt,
        public string $name,
        public string $alias,
        public string $description,
        public ?int $parentId,
    ) {
    }

    public static function fromCategory(?Category $category): ?self
    {
        if (null === $category) {
            return null;
        }

        return new self(
            id: $category->getId(),
            updatedAt: $category->getUpdatedAt(),
            name: $category->getTranslations()[0]->getName(),
            alias: $category->getTranslations()[0]->getAlias(),
            description: $category->getTranslations()[0]->getDescription(),
            parentId: $category->getParent()?->getId() ?? null,
        );
    }
}
