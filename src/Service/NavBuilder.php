<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface as CacheItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NavBuilder
{
    public function __construct(
        private FactoryInterface $factory,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        #[Autowire(service: 'app.navbar_categories')]
        private CacheInterface $cache,
    ) {
    }

    public function mainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'navbar-nav me-auto mb-lg-0');
        $locale = $this->getLocale();

        $cacheKey = \sprintf('top_nav_categories_%s', $locale);
        $categories = $this->cache->get($cacheKey, function (CacheItemInterface $item) use ($locale) {
            $item->expiresAfter(null);
            $item->tag(['navbar_categories']);

            return $this->entityManager
                ->getRepository(Category::class)
                ->fetchForTopNavBar($locale);
        });

        foreach ($categories as $category) {
            $menu->addChild($category->getName($locale), [
                'route' => 'app_index_catalogue_category',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'routeParameters' => [
                    'catAlias' => $category->getAlias($locale),
                ],
            ])->setExtra('translation_domain', false);
        }

        return $menu;
    }

    public function subCategoryMenu(array $options): ItemInterface
    {
        $locale = $this->getLocale();
        $menu = $this->factory->createItem('root');
        /** @var Category|null $category */
        $category = $options['category'] ?? null;

        // Manage top category and sub category page
        $category = null === $category->getParent() ? $category : $category->getParent();
        $menu
            ->setChildrenAttribute('class', 'navbar-subcategory')
            ->setChildrenAttribute('data-cat-label', \sprintf('--- %s ---', $this->translator->trans('category.plural')));

        foreach ($category->getChildren() as $childCategory) {
            $menu->addChild($childCategory->getName($locale), [
                'route' => 'app_index_catalogue_category',
                'attributes' => ['class' => ''],
                'linkAttributes' => ['class' => ''],
                'routeParameters' => [
                    'catAlias' => $category->getAlias($locale),
                    'subCatAlias' => $childCategory->getAlias($locale),
                ],
            ])->setExtra('translation_domain', false);
        }

        return $menu;
    }

    public function footerMenu(): ItemInterface
    {
        $locale = $this->getLocale();
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'footer-nav list-unstyled');
        $pages = $this->entityManager->getRepository(Page::class)->findAll();
        foreach ($pages as $page) {
            $menu->addChild($page->getTitle($locale), [
                'route' => 'app_index_page',
                // 'attributes' => ['class' => ''],
                // 'linkAttributes' => ['class' => 'white-u'],
                'routeParameters' => [
                    'alias' => $page->getAlias($locale),
                ],
            ])->setExtra('translation_domain', false);
        }

        return $menu;
    }

    private function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest() ?? throw new \RuntimeException('No current request found');
    }

    private function getLocale(): string
    {
        return $this->getRequest()->getLocale();
    }
}
