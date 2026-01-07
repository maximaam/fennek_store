<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CatalogueRequestDTO;
use App\DTO\CatalogueResultDTO;
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

    public function resolve(CatalogueRequestDTO $dto): CatalogueResultDTO
    {
        // ─────────────────────────────
        // Product page
        // ─────────────────────────────
        if ($dto->isProductView()) {
            $product = $this->productRepository->find($dto->productId)
                ?? throw new NotFoundHttpException();

            return new CatalogueResultDTO(
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

            $categoryIds = \array_map(function (Category $cat) {
                return $cat->getId();
            }, \iterator_to_array($category->getChildren()));

            $products = $this->productRepository
                ->fetchByCategories($categoryIds)
                ->setMaxResults(12)
                ->getQuery()
                ->getResult();

            return new CatalogueResultDTO(
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

        return new CatalogueResultDTO(
            category: $category,
            products: $products,
            product: null,
            template: 'index/products.html.twig',
        );
    }
}
