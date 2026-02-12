<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $products = $productRepository->findAll();
        $urls = [];
        $locales = ['de', 'en'];
        $alternates = [];

        // Homepage
        foreach ($locales as $locale) {
            $alternates[$locale] = $this->generateUrl(
                'app_index_index',
                ['_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        $urls[] = [
            'alternates' => $alternates,
            'lastmod' => new \DateTime(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        $this->categories($categories, $locales, $urls);
        $this->products($products, $locales, $urls);
        $response = $this->render('sitemap/index.xml.twig', [
            'urls' => $urls,
        ]);

        $response->headers->set('Content-Type', 'application/xml');
        $response->setPublic();
        $response->setMaxAge(86400);



        return $response;
    }

    private function categories(array $categories, array $locales, array &$urls): void
    {
        foreach ($categories as $category) {
            $mainCategory = $category->getParent() ?? $category;
            $subCategory = $category->getParent() ? $category : null;
            $alternates = [];

            foreach ($locales as $locale) {
                $alternates[$locale] = $this->generateUrl(
                    'app_index_catalogue_category',
                    [
                        'catAlias' => $mainCategory->getAlias($locale),
                        'subCatAlias' => $subCategory?->getAlias($locale),
                        '_locale' => $locale,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $urls[] = [
                'alternates' => $alternates,
                'lastmod' => $category->getUpdatedAt(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }
    }

    private function products(array $products, array $locales, array &$urls): void
    {
        foreach ($products as $product) {
            $alternates = [];

            foreach ($locales as $locale) {
                $alternates[$locale] = $this->generateUrl(
                    'app_index_catalogue_category',
                    [
                        'catAlias' => $product->getCategory()->getParent()->getAlias($locale),
                        'subCatAlias' => $product->getCategory()->getAlias($locale),
                        'productAlias' => $product->getTitleSlug($locale),
                        '_locale' => $locale,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $urls[] = [
                'alternates' => $alternates,
                'lastmod' => $product->getUpdatedAt(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }
    }
}
