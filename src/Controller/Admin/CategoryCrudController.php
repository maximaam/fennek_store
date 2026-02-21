<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\CategoryNameFilter;
use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Form\CategoryTranslationType;
use App\Helper\EntityHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
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

    public function createEntity(string $entityFqcn): Category
    {
        $category = new Category();
        foreach (EntityHelper::LOCALES as $locale) {
            $translation = new CategoryTranslation();
            $translation->setLocale($locale);
            $category->addTranslation($translation);
        }

        return $category;
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

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(CategoryNameFilter::new('name', 'Name (DE)', 'de'));
    }

    // This join avoids FETCH::EAGER and reduces the number of queries
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb
            ->leftJoin('entity.translations', 't')
            ->addSelect('t');

        return $qb;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('category.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'category.list')
            ->setPageTitle(Crud::PAGE_NEW, 'category.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Category $c) => $this->translator->trans('label.crud_title.page_edit', ['%item%' => $c->getNameDe()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Category $c) => $this->translator->trans('label.crud_title.page_index', ['%item%' => $c->getNameDe()]))
            // ->showEntityActionsInlined()
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
                    ->andWhere('c.parent IS NULL'),
                // ->orderBy('c.nameDe', 'ASC'),
            ]);

        // Required/disabled only in edit mode
        if (Crud::PAGE_EDIT === $pageName && $category instanceof Category) {
            $isParent = !$category->getParent() instanceof Category;
            $parentCategory->setRequired(!$isParent)->setDisabled($isParent);
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
        yield FormField::addColumn(12);
        yield CollectionField::new('translations')
            ->setEntryType(CategoryTranslationType::class)
            ->setEntryIsComplex()
            ->setFormTypeOptions([
                'by_reference' => false,
                'label' => false,
            ])
            ->allowAdd(false)
            ->allowDelete(false)
            ->setEntryToStringMethod(
                static fn (CategoryTranslation $value, TranslatorInterface $translator): string => $translator->trans(\sprintf('label.%s', $value->getLocale()))
            )
            ->renderExpanded();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield AssociationField::new('parent')
            ->setLabel('category.parent');
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

        yield FormField::addColumn(12);
        yield FormField::addFieldset('label.german');
        yield TextField::new('nameDe', 'label.name.all');
        yield TextField::new('aliasDe', 'label.alias.all');
        yield TextField::new('descriptionDe', 'label.description.all');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('label.english');
        yield TextField::new('nameEn', 'label.name.all');
        yield TextField::new('aliasEn', 'label.alias.all');
        yield TextField::new('descriptionEn', 'label.description.all');
    }
}
