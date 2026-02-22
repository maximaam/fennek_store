<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\TextTypeFilter;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductTranslation;
use App\Enum\Color;
use App\Enum\Size;
use App\Form\MediaImageType;
use App\Form\ProductTranslationType;
use App\Helper\EntityHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    public function createEntity(string $entityFqcn): Product
    {
        $product = new Product();
        foreach (EntityHelper::LOCALES as $locale) {
            $translation = new ProductTranslation();
            $translation->setLocale($locale);
            $product->addTranslation($translation);
        }

        return $product;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb
            ->innerJoin('entity.translations', 't')
            ->innerJoin('entity.category', 'c')
            ->innerJoin('c.translations', 'ct')
            ->addSelect('t, c, ct')
            ->addOrderBy('entity.createdAt', 'DESC');

        return $qb;
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
        return $filters
            ->add('topItem') // BooleanFilter resolves the translated label: product.top_item!
            // ->add(NumericFilter::new('price', 'product.price')) // NumericFilter does not resolve the translated label!
            ->add(EntityFilter::new('category', 'category.singular'))
            ->add(TextTypeFilter::new('title', 'label.title.all', 'de'));
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('product.action.create_new')
            ->setPageTitle(Crud::PAGE_INDEX, 'product.list')
            ->setPageTitle(Crud::PAGE_NEW, 'product.singular')
            ->setPageTitle(Crud::PAGE_EDIT, fn (Product $p) => $this->translator->trans('label.crud_title.page_edit', ['%item%' => $p->getTitleDe()]))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Product $p) => $this->translator->trans('label.crud_title.page_index', ['%item%' => $p->getTitleDe()]))
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
            Crud::PAGE_EDIT, Crud::PAGE_NEW => $this->getFormFields(),
            default => [],
        };
    }

    /**
     * Always redirect to the detail page after save
     * to generate the cached images.
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[\Override]
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $detailUrl = $this->container
            ->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::DETAIL);

        if (Crud::PAGE_NEW === $action) {
            $detailUrl->setEntityId($context->getEntity()->getPrimaryKeyValue());
        }

        return $this->redirect($detailUrl->generateUrl());
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
                    ->innerJoin('c.translations', 'ct', 'WITH', 'ct.locale = :locale')
                    ->andWhere('c.parent IS NOT NULL')
                    ->setParameter('locale', 'de')
                    ->orderBy('c.parent', 'ASC')
                    ->addOrderBy('ct.name', 'ASC'),
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

        yield FormField::addColumn(12);
        yield CollectionField::new('translations')
            ->setEntryType(ProductTranslationType::class)
            ->setEntryIsComplex()
            ->setFormTypeOptions([
                'by_reference' => false,
                'label' => false,
            ])
            ->allowAdd(false)
            ->allowDelete(false)
            ->setEntryToStringMethod(
                static fn (ProductTranslation $value, TranslatorInterface $translator): string => $translator->trans(\sprintf('label.%s', $value->getLocale()))
            )
            ->renderExpanded();

        yield FormField::addColumn(12);
        yield ChoiceField::new('colors')
            ->setLabel('product.colors')
            ->setChoices(array_combine(
                array_map(static fn (string $c) => 'product.colors_list.'.$c, Color::values()),
                Color::values()
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
            ->setChoices(\array_combine(Size::values(), Size::values()))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->addCssClass('checkbox-sizes');

        yield FormField::addColumn(6);
        yield MoneyField::new('price', 'product.price')
            ->setCurrency('EUR');

        yield FormField::addColumn(6);
        yield BooleanField::new('topItem', 'product.top_item');

        yield FormField::addColumn(12);
        yield CollectionField::new('images', 'label.images')
            ->setEntryType(MediaImageType::class)
            ->setEntryToStringMethod(static fn (mixed $image, TranslatorInterface $translator) => $translator->trans('label.image'))
            ->showEntryLabel(false)
            ->allowAdd()
            // ->allowDelete()
            ->renderExpanded()
            ->setFormTypeOptions([
                'by_reference' => false,
                'attr' => [
                    'class' => 'mimosa',
                ],
            ])
            ->onlyOnForms();
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield DateField::new('createdAt', 'date.created_at');
        yield AssociationField::new('category')
            ->setLabel('category.singular');
        yield TextField::new('titleDe', 'label.name.de');
        yield MoneyField::new('price', 'product.price')
            ->setCurrency('EUR');
        yield BooleanField::new('topItem', 'product.top_item');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield DateField::new('createdAt', 'label.date.created_at');
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

        yield CollectionField::new('images', 'label.images')
            ->setTemplatePath('admin/fields/multiple_images.html.twig')
            ->onlyOnDetail();
    }
}
