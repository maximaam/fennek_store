<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Entity\PageTranslation;
use App\Form\MediaImageType;
use App\Form\PageTranslationType;
use App\Helper\EntityHelper;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
    public function createEntity(string $entityFqcn): Page
    {
        $page = new Page();
        foreach (EntityHelper::LOCALES as $locale) {
            $translation = new PageTranslation();
            $translation->setLocale($locale);
            $page->addTranslation($translation);
        }

        return $page;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                static fn (Action $action) => $action->setIcon('fas fa-tags')->setLabel('btn.create_new')
            );
    }

    /*
    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('titleDe');
    }
    */

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('page.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'page.list')
            ->setPageTitle(Crud::PAGE_NEW, 'page.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Page $p) => $this->translator->trans('label.crud_title.page_edit', ['%item%' => $p->getTitleDe()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Page $p) => $this->translator->trans('label.crud_title.page_index', ['%item%' => $p->getTitleDe()]))
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
            Crud::PAGE_EDIT, Crud::PAGE_NEW => $this->getFormFields(),
            default => [],
        };
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getFormFields(): iterable
    {
        yield from $this->getMainFields();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getMainFields(): iterable
    {
        yield FormField::addColumn(12);
        yield CollectionField::new('translations')
            ->setEntryType(PageTranslationType::class)
            ->setEntryIsComplex()
            ->setFormTypeOptions([
                'by_reference' => false,
                'label' => false,
            ])
            ->allowAdd(false)
            ->allowDelete(false)
            ->setEntryToStringMethod(
                static fn (PageTranslation $value, TranslatorInterface $translator): string => $translator->trans(\sprintf('label.%s', $value->getLocale()))
            )
            ->renderExpanded();

        yield FormField::addColumn(12);
        yield CollectionField::new('images', 'label.image')
            ->setEntryType(MediaImageType::class)
            ->setEntryToStringMethod(static fn (mixed $image, TranslatorInterface $translator) => $translator->trans('label.image'))
            ->showEntryLabel(false)
            // ->allowDelete()
            ->renderExpanded()
            ->setFormTypeOptions([
                'by_reference' => false,
                'attr' => [
                    'class' => 'mimosa',
                ],
            ]);
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield TextField::new('titleDe', 'label.title.all');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield DateTimeField::new('createdAt');

        yield FormField::addColumn(12);
        yield DateTimeField::new('updatedAt');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('label.german');
        yield TextField::new('titleDe', 'label.title.all');
        yield TextField::new('descriptionDe', 'label.description.all');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('label.english');
        yield TextField::new('titleEn', 'label.title.all');
        yield TextField::new('descriptionEn', 'label.description.all');

        yield FormField::addColumn(12);
        yield CollectionField::new('images', 'label.images')
            ->setTemplatePath('admin/fields/multiple_images.html.twig')
            ->onlyOnDetail();
    }
}
