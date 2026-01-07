<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('app_index_')]
final class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        dd('index');
        return $this->render('index/index.html.twig');
    }

    #[Route('/{_locale}/catalogue/{catAlias}/{subCatAlias?}/{itemId?}', name: 'catalogue')]
    // #[Cache(expires: 'tomorrow', public: true)]
    public function catalogue(CategoryRepository $categoryRepo, ProductRepository $productRepo, string $catAlias, ?string $subCatAlias = null, ?int $itemId = null, string $_locale = 'en'): Response
    {
        if (null !== $itemId) {
            return $this->render('index/product.html.twig', [
                'product' => $productRepo->find($itemId),
            ]);
        }

        $aliasField = 'alias'.ucfirst($_locale);

        // Main category
        if (null === $subCatAlias) {
            $category = $categoryRepo->findOneBy([$aliasField => $catAlias]);
            $categoryIds = \array_map(function (Category $cat) {
                return $cat->getId();
            }, \iterator_to_array($category->getChildren()));

            $products = $productRepo
                ->fetchByCategories($categoryIds)
                ->setMaxResults(12)
                ->getQuery()
                ->getResult();
        } // Sub-category
        else {
            $category = $categoryRepo->findOneBy([$aliasField => $subCatAlias]);
            $products = $productRepo->findBy(['category' => $category]);
        }

        return $this->render('index/products.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
