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

        $subCategories = $this->getSubCategories($category, $dto->locale);
        $categoryViewDto = CategoryViewDto::fromCategory($category, $dto->locale);

        if ($dto->isCategoryView()) {
            return $this->buildCategoryResult(
                $category,
                $categoryViewDto,
                $subCategories,
                $dto,
            );
        }

        return $this->buildProductResult(
            $categoryViewDto,
            $subCategories,
            $dto,
        );
    }

    /**
     * @return array<mixed, mixed>
     */
    private function getSubCategories(Category $category, string $locale): array
    {
        $parentId = $category->getParent()?->getId() ?? $category->getId();

        return $this->categoryRepository->fetchSubCategoriesByParentId(
            (int) $parentId,
            $locale,
        );
    }

    /**
     * @param array<string, mixed> $subCategories
     */
    private function buildCategoryResult(Category $category, CategoryViewDto $categoryViewDto, array $subCategories, CatalogueRequestDto $dto): CatalogueResultDto
    {
        $subCategoryIds = $this->resolveSubCategoryIds($category, $categoryViewDto, $subCategories);
        $products = $this->productRepository->fetchByCategories($subCategoryIds, $dto->locale, 60);

        return new CatalogueResultDto(
            category: $categoryViewDto,
            subCategories: $subCategories,
            products: $products,
            product: null,
            template: 'index/products.html.twig',
        );
    }

    /**
     * @param array<string, mixed> $subCategories
     */
    private function buildProductResult(CategoryViewDto $categoryViewDto, array $subCategories, CatalogueRequestDto $dto): CatalogueResultDto
    {
        $product = $this->productRepository
            ->fetchOneBy(['slug' => $dto->productSlug], $dto->locale)
            ?? throw new NotFoundHttpException(
                \sprintf('Product with slug "%s" not found.', $dto->productSlug)
            );

        return new CatalogueResultDto(
            category: $categoryViewDto,
            subCategories: $subCategories,
            products: null,
            product: ProductViewDto::fromProduct($product, $dto->locale),
            template: 'index/product.html.twig',
        );
    }

    /**
     * @param array<string, mixed> $subCategories
     *
     * @return array<int, int|null>
     */
    private function resolveSubCategoryIds(Category $category, CategoryViewDto $categoryViewDto, array $subCategories): array
    {
        $subCategoryIds = array_column($subCategories, 'id');

        if (!$category->getParent() instanceof Category) {
            shuffle($subCategoryIds);

            return \array_slice($subCategoryIds, 0, 3);
        }

        return [$categoryViewDto->id];
    }
}
