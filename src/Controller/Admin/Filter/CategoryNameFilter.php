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

final class CategoryNameFilter implements FilterInterface
{
    use FilterTrait;

    private string $property;
    private string $locale;

    public static function new(string $property, string $label, string $locale): self
    {
        $filter = new self()
            ->setProperty($property)
            ->setLabel($label)
            ->setFormType(TextType::class);

        $filter->property = $property;
        $filter->locale = $locale;

        return $filter;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if (null === $filterDataDto->getValue()) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $joinAlias = 'translation_'.$this->property;

        // Prevent duplicate joins
        if (!\in_array($joinAlias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin($alias.'.translations', $joinAlias);
        }

        $queryBuilder
            ->andWhere(\sprintf(
                '%s.%s LIKE :%s',
                $joinAlias,
                $this->property,
                $this->property
            ))
            ->andWhere(\sprintf(
                '%s.locale = :locale_%s',
                $joinAlias,
                $this->property
            ))
            ->setParameter(
                $this->property,
                '%'.$filterDataDto->getValue().'%'
            )
            ->setParameter(
                'locale_'.$this->property,
                $this->locale
            );
    }
}
