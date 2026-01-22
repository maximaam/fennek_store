<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Purchase;
use App\Helper\PurchaseEaCrudHelper;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Product>
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
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('orderId');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('purchase.action.create_new')
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
            default => [],
        };
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getIndexFields(): iterable
    {
        yield DateField::new('createdAt', 'date.created_at');
        yield TextField::new('orderId', 'purchase.id');
        yield TextField::new('status.value', 'purchase.status')
            ->formatValue(fn (string $status) => $this->translator->trans('purchase.status.'.strtolower($status)));
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    private function getDetailFields(): iterable
    {
        yield FormField::addColumn(6);
        yield FormField::addFieldset('purchase.singular');
        yield DateField::new('createdAt', 'date.created_at');
        yield TextField::new('orderId', 'purchase.id');
        yield TextField::new('orderId', 'purchase.payer.name')
            ->formatValue(fn (string $o, Purchase $purchase) => PurchaseEaCrudHelper::getAmount($purchase->getPayload()));


        yield FormField::addColumn(6);
        yield FormField::addFieldset('purchase.buyer');
        yield TextField::new('orderId', 'purchase.payer.name')
            ->formatValue(fn (string $o, Purchase $purchase) => PurchaseEaCrudHelper::getPayerName($purchase->getPayload()));
        yield TextField::new('orderId', 'purchase.payer.email')
            ->formatValue(fn (string $o, Purchase $purchase) => PurchaseEaCrudHelper::getPayerEmail($purchase->getPayload()));
    }
}
