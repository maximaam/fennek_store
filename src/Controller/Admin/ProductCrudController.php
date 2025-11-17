<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Product>
 */
final class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                static fn (Action $action) => $action->setIcon('fas fa-tags')->setLabel('product.action.create_new')
            );
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('titleDe')
            ->add('titleEn')
            ->add('topItem');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('product.action.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'product.list')
            ->setPageTitle(Crud::PAGE_NEW, 'product.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Product $p) => $this->translator->trans('product.title.page_edit', ['%product%' => $p->getTitleDe()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Product $p) => $this->translator->trans('product.title.page_index', ['%product%' => $p->getTitleDe()]))
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
        $category = AssociationField::new('category')
            ->setLabel('category.singular')
            ->renderAsNativeWidget()
            ->setFormTypeOptions([
                'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere('c.parent IS NOT NULL')
                    ->orderBy('c.parent', 'ASC')
                    ->addOrderBy('c.nameDe', 'ASC'),
                'choice_label' => static function (Category $c) {
                    if ($c->getParent() instanceof Category) {
                        return \sprintf('%s → %s', $c->getParent()->getNameDe(), $c->getNameDe());
                    }

                    return $c->getNameDe();
                },
            ]);

        yield FormField::addColumn(6);
        yield $category;

        yield FormField::addColumn(6);
        yield TextField::new('itemNumber', 'product.item_number');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('label.german');
        yield TextField::new('titleDe', 'label.title.de');
        yield TextareaField::new('descriptionDe', 'label.description.de');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('label.english');
        yield TextField::new('titleEn', 'label.title.en');
        yield TextareaField::new('descriptionEn', 'label.description.en');

        yield FormField::addColumn(12);
        yield ChoiceField::new('colors')
            ->setLabel('product.colors')
            ->setChoices(array_combine(
                array_map(static fn ($c) => "product.colors_list.$c", Product::COLORS),
                Product::COLORS
            ))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->addCssClass('checkbox-colors')
            ->setFormTypeOptions([
                'choice_attr' => static fn (string $color) => [
                    'style' => \sprintf('border-left: 10px solid %s; padding-left: 20px;', $color),
                ],
            ]);

        yield FormField::addColumn(12);
        yield ChoiceField::new('sizes')
            ->setLabel('product.sizes')
            ->setChoices(\array_combine(Product::SIZES, Product::SIZES))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->addCssClass('checkbox-sizes');

        yield FormField::addColumn(3);
        yield MoneyField::new('price', 'label.price')
            ->setCurrency('EUR');

        yield FormField::addColumn(3);
        yield BooleanField::new('topItem', 'product.top_item');

        yield FormField::addColumn(6);
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield AssociationField::new('category')->setLabel('category.singular');
        yield TextField::new('titleDe', 'label.name.de');
        yield TextField::new('titleEn', 'label.name.en');
        yield BooleanField::new('topItem', 'product.top_item');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield TextField::new('category', 'category.singular');
        yield FormField::addColumn(12);
        yield TextField::new('itemNumber', 'product.item_number');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('label.german');
        yield TextField::new('titleDe', 'label.title.all');
        // yield TextField::new('aliasDe');
        yield TextField::new('descriptionDe', 'label.description.all');

        yield FormField::addColumn(6);
        yield FormField::addFieldset('label.english');
        yield TextField::new('titleEn', 'label.title.all');
        // yield TextField::new('aliasEn');
        yield TextField::new('descriptionEn', 'label.description.all');

        yield FormField::addColumn(12);
        yield ArrayField::new('colors', 'product.colors')
            ->setTemplatePath('admin/fields/simple_array.html.twig');
        yield FormField::addColumn(12);
        yield ArrayField::new('sizes', 'product.sizes')
            ->setTemplatePath('admin/fields/simple_array.html.twig');

        yield FormField::addColumn(12);
        yield MoneyField::new('price', 'product.price')
            ->setCurrency('EUR');
    }
}
