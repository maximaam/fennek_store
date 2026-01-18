<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NavBuilder
{
    private Request $request;

    public function __construct(
        private FactoryInterface $factory,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
        $this->request = $this->requestStack->getCurrentRequest() ?? throw new \RuntimeException('No current request found');
    }

    public function mainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'navbar-nav me-auto mb-lg-0');
        $locale = $this->request->getLocale();
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

    public function subCategoryMenu(): ItemInterface
    {
        $locale = $this->request->getLocale();
        $menu = $this->factory->createItem('root');
        $menu
            ->setChildrenAttribute('class', 'navbar-subcategory')
            ->setChildrenAttribute('data-cat-label', \sprintf('--- %s ---', $this->translator->trans('category.plural')));

        $repository = $this->entityManager->getRepository(Category::class);
        $currentCategory = $repository
            ->findOneBy(['alias'.ucfirst($locale) => $this->request->request->get('catAlias')])
            ?? throw new \RuntimeException('No current category found');

        $subCategories = $repository
            ->fetchChildren($currentCategory)
            ->getQuery()
            ->getResult();

        foreach ($subCategories as $category) {
            $menu->addChild($category->getName($locale), [
                'route' => 'app_index_catalogue',
                'attributes' => ['class' => ''],
                'linkAttributes' => ['class' => ''],
                'routeParameters' => [
                    'catAlias' => $currentCategory->getAlias($locale),
                    'subCatAlias' => $category->getAlias($locale),
                ],
            ]);
        }

        return $menu;
    }

    public function footerMenu(): ItemInterface
    {
        $locale = $this->request->getLocale();
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
