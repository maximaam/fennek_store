<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CatalogueRequestDto;
use App\Dto\CatalogueResultDto;
use App\Helper\EntityHelper;
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
        if ($dto->isProductView()) {
            $titleSlug = \sprintf('title%sSlug', ucfirst($dto->locale));
            $product = $this->productRepository
                ->findOneBy([$titleSlug => $dto->productAlias])
                ?? throw new NotFoundHttpException();

            return new CatalogueResultDto(
                category: null,
                products: null,
                product: $product,
                template: 'index/product.html.twig',
            );
        }

        if ($dto->isMainCategoryView()) {
            $category = $this->categoryRepository
                ->fetchOneInDepthByAlias($dto->categoryAlias, $dto->locale)
                ?? throw new NotFoundHttpException();

            $categoryIds = EntityHelper::getCategoryChildrenIds($category);
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
        $category = $this->categoryRepository
            ->fetchOneInDepthByAlias($dto->subCategoryAlias, $dto->locale)
            ?? throw new NotFoundHttpException();

        return new CatalogueResultDto(
            category: $category,
            products: $this->productRepository->findBy(['category' => $category]),
            product: null,
            template: 'index/products.html.twig',
        );
    }
}
