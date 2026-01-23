<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Purchase;
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
            ->setEntityLabelInSingular('purchase.singular')
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
        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.singular');
        yield DateField::new('createdAt', 'date.created_at');
        yield TextField::new('orderId', 'purchase.id');
        yield ArrayField::new('product', 'product.plural')
            ->setTemplatePath('admin/fields/purchase_product.html.twig');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.payer.singular');
        yield ArrayField::new('payload', 'purchase.payer.name')
            ->setTemplatePath('admin/fields/purchase_payload.html.twig')
            ->setCustomOption('target', 'payer_name');
        yield ArrayField::new('payload', 'purchase.payer.email')
            ->setTemplatePath('admin/fields/purchase_payload.html.twig')
            ->setCustomOption('target', 'payer_email');

        yield FormField::addColumn(12);
        yield FormField::addFieldset('purchase.delivery.singular');
        yield ArrayField::new('payload', 'purchase.delivery.name')
            ->setTemplatePath('admin/fields/purchase_payload.html.twig')
            ->setCustomOption('target', 'delivery_name');
        yield ArrayField::new('payload', 'purchase.delivery.address')
            ->setTemplatePath('admin/fields/purchase_payload.html.twig')
            ->setCustomOption('target', 'delivery_address');
    }
}
