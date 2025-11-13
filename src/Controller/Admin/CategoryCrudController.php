<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Category>
 */
final class CategoryCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                static fn (Action $action) => $action->setIcon('fas fa-tags')->setLabel('category.action.create_new')
            );
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('nameDe');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('category.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'category.list')
            ->setPageTitle(Crud::PAGE_NEW, 'category.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Category $c) => $this->translator->trans('category.title.page_edit', ['%category%' => $c->getNameDe()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Category $c) => $this->translator->trans('category.title.page_index', ['%category%' => $c->getNameDe()]))
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
        /** @var Category|null $category */
        $category = $this->getContext()?->getEntity()->getInstance();

        $parentCategory = AssociationField::new('parent')
            ->setLabel('category.parent')
            ->renderAsNativeWidget()
            ->setFormTypeOptions([
                'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.parent IS NULL')
                    ->orderBy('c.nameDe', 'ASC'),
            ]);

        // Required/disabled only in edit mode
        if (Crud::PAGE_EDIT === $pageName && $category instanceof Category) {
            $isTop = !$category->getParent() instanceof Category;
            $parentCategory->setRequired(!$isTop)->setDisabled($isTop);
        }

        yield FormField::addColumn(6);
        yield $parentCategory;

        // Position only visible for top categories on edit
        yield FormField::addColumn(6);
        if (Crud::PAGE_EDIT === $pageName && null === $category?->getParent()) {
            yield IntegerField::new('position')->setHelp('Nur bei TOP Kategorien');
        }

        yield from $this->getMainFields();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getMainFields(): iterable
    {
        // German fields
        yield FormField::addColumn(6);
        yield FormField::addFieldset('Deutsch');
        yield TextField::new('nameDe', 'category.name.de');
        yield TextareaField::new('descriptionDe', 'category.description.de');

        // English fields
        yield FormField::addColumn(6);
        yield FormField::addFieldset('English');
        yield TextField::new('nameEn', 'category.name.en');
        yield TextareaField::new('descriptionEn', 'category.description.en');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield AssociationField::new('parent')->setLabel('category.parent');
        yield TextField::new('nameDe', 'Name');
        yield IntegerField::new('position');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield IntegerField::new('position');
        yield DateTimeField::new('createdAt');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('Deutsch');
        yield TextField::new('nameDe');
        yield TextField::new('aliasDe');
        yield TextField::new('descriptionDe');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('English');
        yield TextField::new('nameEn');
        yield TextField::new('aliasEn');
        yield TextField::new('descriptionEn');
    }
}
