<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogueRequestDto;
use App\Dto\CatalogueResultDto;
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
        // ─────────────────────────────
        // Product page
        // ─────────────────────────────
        if ($dto->isProductView()) {
            $product = $this->productRepository->find($dto->productId)
                ?? throw new NotFoundHttpException();

            return new CatalogueResultDto(
                category: null,
                products: null,
                product: $product,
                template: 'index/product.html.twig',
            );
        }

        $aliasField = 'alias'.ucfirst($dto->locale);

        // ─────────────────────────────
        // Main category
        // ─────────────────────────────
        if ($dto->isMainCategoryView()) {
            $category = $this->categoryRepository
                ->findOneBy([$aliasField => $dto->categoryAlias])
                ?? throw new NotFoundHttpException();

            $categoryIds = \array_map(static fn (Category $cat) => $cat->getId(), \iterator_to_array($category->getChildren()));
            $products = $this->productRepository
                ->fetchByCategories($categoryIds)
                ->setMaxResults(12)
                ->getQuery()
                ->getResult();

            return new CatalogueResultDto(
                category: $category,
                products: $products,
                product: null,
                template: 'index/products.html.twig',
            );
        }

        // ─────────────────────────────
        // Sub-category
        // ─────────────────────────────
        $category = $this->categoryRepository->findOneBy([
            $aliasField => $dto->subCategoryAlias,
        ]) ?? throw new NotFoundHttpException();

        $products = $this->productRepository->findBy([
            'category' => $category,
        ]);

        return new CatalogueResultDto(
            category: $category,
            products: $products,
            product: null,
            template: 'index/products.html.twig',
        );
    }
}
