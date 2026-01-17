<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CatalogueRequestDto;
use App\Entity\Page;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use App\Service\CatalogueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'app_index_')]
final class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ProductRepository $productRepo): Response
    {
        return $this->render('index/index.html.twig', [
            'products' => $productRepo->findBy(['topItem' => true], ['updatedAt' => 'DESC'], 16),
        ]);
    }

    #[Route('/{_locale}/page/{alias}', name: 'page')]
    public function page(PageRepository $pageRepo, string $alias, string $_locale): Response
    {
        $aliasI18n = 'alias'.ucfirst($_locale);
        $page = $pageRepo->findOneBy([$aliasI18n => $alias]);

        if (!$page instanceof Page) {
            throw $this->createNotFoundException(\sprintf('Page with alias "%s" not found', $alias));
        }

        return $this->render('index/page.html.twig', ['page' => $page]);
    }

    #[Route('/{_locale}/search', name: 'search', methods: [Request::METHOD_POST])]
    public function search(Request $request, ProductRepository $productRepo): Response
    {
        $query = trim((string) $request->query->get('query', ''));
        if (\mb_strlen($query) < 3) {
            return $this->redirectToRoute('app_index_index');
        }

        $query = mb_strtolower($query);
        $products = $productRepo->searchTitle($query, $request->getLocale());

        return $this->render('index/search.html.twig', [
            'query' => $query,
            'products' => $products,
        ]);
    }

    #[Route('/{_locale}/catalogue/{catAlias}/{subCatAlias?}/{itemId?}', name: 'catalogue')]
    // #[Cache(expires: 'tomorrow', public: true)]
    public function catalogue(CatalogueResolver $resolver, string $_locale, string $catAlias, ?string $subCatAlias = null, ?int $itemId = null): Response
    {
        $requestDTO = new CatalogueRequestDto($_locale, $catAlias, $subCatAlias, $itemId);
        $result = $resolver->resolve($requestDTO);

        return $this->render($result->template, [
            'category' => $result->category,
            'products' => $result->products,
            'product' => $result->product,
        ]);
    }
}
