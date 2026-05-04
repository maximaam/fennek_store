<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\MediaImage;
use App\Entity\Product;

final readonly class ProductViewDto
{
    /**
     * @param array<string>|null      $sizes
     * @param array<string>           $colors
     * @param array<int, string|null> $images
     */
    public function __construct(
        public ?int $id,
        public ?string $itemNumber,
        public int $price,
        public ?array $sizes,
        public array $colors,
        public bool $topItem,
        public ?\DateTimeImmutable $updatedAt,
        public string $title,
        public string $slug,
        public string $description,
        public array $images,
    ) {
    }

    public static function fromProduct(Product $product, string $locale): self
    {
        $translation = $product->getTranslationByLocale($locale);

        return new self(
            id: $product->getId(),
            itemNumber: $product->getItemNumber(),
            price: $product->getPrice(),
            sizes: $product->getSizes(),
            colors: $product->getColors(),
            topItem: $product->isTopItem(),
            updatedAt: $product->getUpdatedAt(),
            title: $translation->getTitle(),
            slug: $translation->getSlug(),
            description: $translation->getDescription(),
            images: $product->getImages()->map(static fn (MediaImage $image) => $image->getImageName())->toArray(),
        );
    }
}
