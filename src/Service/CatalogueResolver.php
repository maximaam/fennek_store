<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogueRequestDto;
use App\Dto\CatalogueResultDto;
use App\Dto\CategoryViewDto;
use App\Dto\ProductViewDto;
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
            ?? throw new NotFoundHttpException();

        $subCategories = $this->categoryRepository
            ->fetchSubCategoriesByParentId(
                $category->getParent() ? $category->getParent()->getId() : $category->getId(),
                $dto->locale,
            );
        $category = CategoryViewDto::fromCategory($category);

        if ($dto->isCategoryView()) {
            $subCategoryIds = $category->parentId ? [$category->id] : array_column($subCategories, 'id');

            return new CatalogueResultDto(
                category: $category,
                subCategories: $subCategories,
                products: $this->productRepository->fetchByCategories($subCategoryIds, $dto->locale, 60),
                product: null,
                template: 'index/products.html.twig',
            );
        }

        $product = $this->productRepository
            ->fetchOneBy(['slug' => $dto->productSlug], $dto->locale)
            ?? throw new NotFoundHttpException();

        return new CatalogueResultDto(
            category: $category,
            subCategories: $subCategories,
            products: null,
            product: ProductViewDto::fromProduct($product),
            template: 'index/product.html.twig',
        );
    }
}
