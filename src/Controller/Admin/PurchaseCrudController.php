<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Purchase;
use App\Enum\PayPalStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Purchase>
 */
final class PurchaseCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
        // ->disable(Action::DELETE, Action::NEW);
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(
                ChoiceFilter::new('status')
                    ->setChoices(array_combine(
                        array_map(fn (string $s) => $this->translator->trans('purchase.status.'.strtolower($s)), PayPalStatus::values()),
                        PayPalStatus::values(),
                    ))
            );
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('purchase.singular')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(25)
            ->setPageTitle(Crud::PAGE_INDEX, 'purchase.plural')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Purchase $p) => $this->translator->trans('purchase.page_edit_title', ['%item%' => $p->getOrderId()]))
            ->setDefaultSort(['createdAt' => 'DESC', 'status' => 'DESC']);
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
            Crud::PAGE_EDIT => $this->getEditFields(),
            default => [],
        };
    }

    /*
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $appliedFilters = $searchDto->getAppliedFilters();
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (!\array_key_exists('status', $appliedFilters)) {
            $qb
                ->andWhere('entity.status = :status')
                ->setParameter('status', PayPalStatus::COMPLETED->value);
        }

        return $qb;
    }
    */

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield DateTimeField::new('createdAt', 'date.created_at');
        yield TextField::new('status.value', 'purchase.status.singular')
            ->formatValue(fn (string $status) => $this->translator->trans('purchase.status.'.strtolower($status)));
        yield TextField::new('buyerFullName', 'purchase.payer.name');
        yield MoneyField::new('totalAmount', 'purchase.total_amount')
            ->setCurrency('EUR');
        yield BooleanField::new('shipped', 'purchase.shipping.shipped')
            ->renderAsSwitch(false);
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.singular');
        yield DateTimeField::new('createdAt', 'date.created_at');
        yield TextField::new('orderId', 'purchase.id');
        yield MoneyField::new('totalAmount', 'purchase.total_amount')
            ->setCurrency('EUR');
        yield ArrayField::new('product', 'product.plural')
            ->setTemplatePath('admin/fields/purchase_product.html.twig');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.payer.singular');
        yield ArrayField::new('payment', 'purchase.payer.name')
            ->setTemplatePath('admin/fields/purchase_payment.html.twig')
            ->setCustomOption('target', 'payer_name');
        yield ArrayField::new('payment', 'purchase.payer.emails')
            ->setTemplatePath('admin/fields/purchase_payment.html.twig')
            ->setCustomOption('target', 'payer_email');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.shipping.singular');
        yield ArrayField::new('payment', 'purchase.shipping.name')
            ->setTemplatePath('admin/fields/purchase_payment.html.twig')
            ->setCustomOption('target', 'shipping_name');
        yield ArrayField::new('payment', 'purchase.shipping.address')
            ->setTemplatePath('admin/fields/purchase_payment.html.twig')
            ->setCustomOption('target', 'shipping_address');
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getEditFields(): iterable
    {
        yield BooleanField::new('shipped', 'purchase.shipping.shipped')
            ->setHelp('purchase.shipping.shipped_help');
    }
}
