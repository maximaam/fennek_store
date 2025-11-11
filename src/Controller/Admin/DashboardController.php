<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    #[\Override]
    public function index(): Response
    {
        $urlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($urlGenerator->setController(ProductCrudController::class)
            ->generateUrl());

        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    #[\Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setFaviconPath('/images/favicon.png')
            ->setTitle('<img src="/images/logo.png"> FS Admin');
    }

    #[\Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('dashboard', 'fas fa-home');
        yield MenuItem::linkToCrud('category.plural', 'fas fa-tags', Category::class);
        yield MenuItem::linkToCrud('product.plural', 'fas fa-box', Product::class);
    }
}
