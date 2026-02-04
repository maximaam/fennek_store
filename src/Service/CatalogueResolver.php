<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogueRequestDto;
use App\Dto\CatalogueResultDto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class CatalogueResolver
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        // private CacheInterface $cache,
    ) {
    }

    public function resolve(CatalogueRequestDto $dto): CatalogueResultDto
    {
        /*
        $cacheKey = \sprintf(
            'catalogue_%s_%s_%s_%s',
            $dto->locale,
            $dto->categoryAlias,
            $dto->subCategoryAlias ?? 'all',
            $dto->productId ?? 'list'
        );

        $this->cache->delete($cacheKey);
        */

        return $this->doResolve($dto);

        /*

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto) {
            $item->expiresAfter(600); // 10 minutes

            // 👉 THIS is where it comes from
            return $this->doResolve($dto);
        });
        */
    }

    private function doResolve(CatalogueRequestDto $dto): CatalogueResultDto
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
