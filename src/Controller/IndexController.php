<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CatalogueRequestDto;
use App\Entity\Page;
use App\Helper\EntityHelper;
use App\Repository\PageRepository;
use App\Repository\ProductRepository;
use App\Service\CatalogueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface as CacheItemInterface;

#[Route(
    '/{_locale}',
    name: 'app_index_',
    requirements: ['_locale' => 'en|de'],
    defaults: ['_locale' => 'de']
)]
final class IndexController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'app.navbar_categories')]
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/', name: 'index', methods: [Request::METHOD_GET])]
    #[Cache(maxage: 86400, smaxage: 86400, public: true)]
    public function index(ProductRepository $productRepo, string $_locale): Response
    {
        $cacheKey = \sprintf('index_page_products_%s', $_locale);
        $products = $this->cache->get($cacheKey, static function (CacheItemInterface $item) use ($productRepo, $_locale) {
            $item->expiresAfter(null);
            $item->tag(['index_page_products']);

            return $productRepo->fetchForIndexPage($_locale);
        });

        return $this->render('index/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/page/{alias}', name: 'page', methods: [Request::METHOD_GET])]
    #[Cache(maxage: 86400, smaxage: 86400, public: true)]
    public function page(PageRepository $pageRepo, string $alias, string $_locale): Response
    {
        $aliasI18n = 'alias'.ucfirst($_locale);
        $page = $pageRepo->findOneBy([$aliasI18n => $alias]);

        if (!$page instanceof Page) {
            throw $this->createNotFoundException(\sprintf('Page with alias "%s" not found', $alias));
        }

        return $this->render('index/page.html.twig', ['page' => $page]);
    }

    #[Route('/search', name: 'search', methods: [Request::METHOD_GET])]
    public function search(Request $request, ProductRepository $productRepo): Response
    {
        $query = trim($request->query->get('query', ''));
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

    #[Route('/{category}/{productSlug?}', name: 'catalogue', methods: [Request::METHOD_GET])]
    public function catalogue(Request $request, CatalogueResolver $resolver, string $_locale, string $category, ?string $productSlug = null): Response
    {
        $requestDTO = new CatalogueRequestDto($_locale, $category, $productSlug);
        $result = $resolver->resolve($requestDTO);

        /**
         * The goal here is to make reload of the page
         * return 304 response instead of 200 using last modified header.
         * Otherwise, the #[Cache is enough.
         */
        $dates = array_filter([
            $result->product->updatedAt ?? null,
            $result->category->updatedAt ?? null,
        ]);

        $lastModified = ([] !== $dates) ? max($dates) : new \DateTimeImmutable();
        $response = new Response()
            ->setPublic()
            ->setMaxAge(86400)
            ->setSharedMaxAge(86400)
            ->setLastModified($lastModified);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render($result->template, [
            'category' => $result->category,
            'sub_categories' => $result->subCategories,
            'products' => $result->products,
            'product' => $result->product,
        ], $response);
    }
}
