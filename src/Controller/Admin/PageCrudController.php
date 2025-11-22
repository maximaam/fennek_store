<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Page>
 */
final class PageCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                static fn (Action $action) => $action->setIcon('fas fa-tags')->setLabel('page.action.create_new')
            );
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('title');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('page.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'page.list')
            ->setPageTitle(Crud::PAGE_NEW, 'page.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Page $p) => $this->translator->trans('page.title.page_edit', ['%page%' => $p->getTitle()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Page $p) => $this->translator->trans('page.title.page_index', ['%page%' => $p->getTitle()]))
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(25);
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return match ($pageName) {
            Crud::PAGE_INDEX => $this->getIndexFields(),
            Crud::PAGE_DETAIL => $this->getDetailFields(),
            Crud::PAGE_EDIT, Crud::PAGE_NEW => $this->getFormFields($pageName),
            default => [],
        };
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getFormFields(string $pageName): iterable
    {
        /** @var Page|null $page */
        $page = $this->getContext()?->getEntity()->getInstance();

        yield from $this->getMainFields();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getMainFields(): iterable
    {
        // German fields
        yield FormField::addColumn(12);
        yield TextField::new('title', 'label.title');
        yield TextareaField::new('description', 'label.description');

        yield ImageField::new('graphic', 'Image')
            ->setBasePath('public/uploads/images')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setFormTypeOptions(['required' => false])
            ->setUploadDir('public/uploads/images');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield TextField::new('title', 'label.title');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield DateTimeField::new('createdAt');

        yield FormField::addColumn(6);
        yield TextField::new('title');
        yield TextField::new('description');
        yield ImageField::new('graphic.imageName')
            ->setBasePath('/uploads/images');
    }
}
