<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogueRequestDto;
use App\Dto\CatalogueResultDto;
use App\Dto\CategoryViewDto;
use App\Dto\ProductViewDto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CatalogueResolver
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
    ) {
    }

    public function resolve(CatalogueRequestDto $dto): CatalogueResultDto
    {
        $category = $this->categoryRepository
            ->fetchOneByAlias($dto->category, $dto->locale)
            ?? throw new NotFoundHttpException(\sprintf('Category with alias "%s" not found.', $dto->category));

        $subCategories = $this->categoryRepository->fetchSubCategoriesByParentId(
            (int) ($category->getParent() instanceof Category ? $category->getParent()->getId() : $category->getId()),
            $dto->locale,
        );

        $categoryViewDto = CategoryViewDto::fromCategory($category, $dto->locale);
        if ($dto->isCategoryView()) {
            $subCategoryIds = [] !== $subCategories ? [$categoryViewDto->id] : array_column($subCategories, 'id');

            return new CatalogueResultDto(
                category: $categoryViewDto,
                subCategories: $subCategories,
                products: $this->productRepository->fetchByCategories($subCategoryIds, $dto->locale, 60),
                product: null,
                template: 'index/products.html.twig',
            );
        }

        $product = $this->productRepository
            ->fetchOneBy(['slug' => $dto->productSlug], $dto->locale)
            ?? throw new NotFoundHttpException(\sprintf('Product with slug "%s" not found.', $dto->productSlug));

        return new CatalogueResultDto(
            category: $categoryViewDto,
            subCategories: $subCategories,
            products: null,
            product: ProductViewDto::fromProduct($product, $dto->locale),
            template: 'index/product.html.twig',
        );
    }
}
