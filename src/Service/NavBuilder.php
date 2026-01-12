<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NavBuilder
{
    public function __construct(
        private FactoryInterface $factory,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public function mainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'navbar-nav me-auto mb-lg-0');
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        $categories = $this->entityManager->getRepository(Category::class)->fetchForTopNavBar();
        foreach ($categories as $category) {
            $menu->addChild($category->getName($locale), [
                'route' => 'app_index_catalogue',
                'attributes' => ['class' => 'nav-item'],
                'linkAttributes' => ['class' => 'nav-link'],
                'routeParameters' => [
                    'catAlias' => $category->getAlias($locale),
                ],
            ]);
        }

        return $menu;
    }

    public function footerMenu(): ItemInterface
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'footer-nav list-unstyled');
        $pages = $this->entityManager->getRepository(Page::class)->findAll();
        foreach ($pages as $page) {
            if ('home' === $page->getAlias($locale)) {
                continue;
            }

            $menu->addChild('> '.$page->getTitle($locale), [
                'route' => 'app_index_page',
                // 'attributes' => ['class' => ''],
                // 'linkAttributes' => ['class' => 'white-u'],
                'routeParameters' => [
                    'alias' => $page->getAlias($locale),
                ],
            ]);
        }

        return $menu;
    }
}
