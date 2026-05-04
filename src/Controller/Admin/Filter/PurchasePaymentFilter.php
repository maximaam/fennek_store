<?php

declare(strict_types=1);

namespace App\Controller\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class PurchasePaymentFilter implements FilterInterface
{
    use FilterTrait;

    private string $property;

    public static function new(string $property, string $label): self
    {
        $filter = new self()
            ->setProperty($property)
            ->setLabel($label)
            ->setFormType(TextType::class);

        $filter->property = $property;

        return $filter;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $value = $filterDataDto->getValue();

        if (\is_scalar($value)) {
            $alias = $filterDataDto->getEntityAlias();
            $jsonPath = '$.payer.'.$this->property;
            $queryBuilder
                ->andWhere(\sprintf('LOWER(JSON_EXTRACT(%s.payment, :jsonPath)) LIKE LOWER(:value)', $alias))
                ->setParameter('jsonPath', $jsonPath)
                ->setParameter('value', '%'.$value.'%');
        }
    }
}
