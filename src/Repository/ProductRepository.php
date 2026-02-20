<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public const string QB_ALIAS = 'p';
    public const string QB_ALIAS_TRANSLATION = 'pt';
    public const string QB_ALIAS_IMAGE = 'pi';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function fetchForIndexPage(string $locale, int $limit = 20): array
    {
        return $this->baseQueryProducts($locale)
            ->andWhere('p.topItem = true')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function fetchOneBy(array $field, string $locale): ?Product
    {
        return $this->baseQuery($locale)
            ->andWhere(\sprintf('pt.%s = :target', key($field)))
            ->setParameter('target', current($field))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function fetchOneFlatBy(array $field, string $locale): ?array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.translations', 'pt', 'WITH', 'pt.locale = :locale')
            ->andWhere('pt.locale = :locale')
            ->setParameter('locale', $locale)
            ->leftJoin('p.images', 'i')
            ->addSelect('i, pt')
            ->andWhere(\sprintf('pt.%s = :target', key($field)))
            ->setParameter('target', current($field))
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    private function baseQuery(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.translations', 'pt', 'WITH', 'pt.locale = :locale')
            ->andWhere('pt.locale = :locale')
            ->setParameter('locale', $locale);
    }

    /**
     * @param array<int, int|null> $categories
     */
    public function fetchByCategories(array $categories, string $locale): array
    {
        return $this->baseQueryProducts($locale)
            ->andWhere('c.id IN(:categories)')
            ->setParameter('categories', $categories)
            ->setMaxResults(12)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return Product[]
     */
    public function searchTitle(string $query, string $locale, int $limit = 10): array
    {
        return $this->baseQueryProducts($locale)
            ->andWhere('pt.title LIKE :title')
            ->setParameter('title', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    private function baseQueryProducts(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->innerJoin('p.translations', 'pt', 'WITH', 'pt.locale = :locale')
            ->innerJoin('c.translations', 'ct', 'WITH', 'ct.locale = :locale')
            ->leftJoin('c.parent', 'cp')
            ->leftJoin('cp.translations', 'cpt', 'WITH', 'cpt.locale = :locale')
            ->leftJoin( // Subquery to get the first image of the product
                'p.images',
                'mi',
                'WITH',
                'mi.id = (
                SELECT MIN(mi2.id)
                FROM App\Entity\MediaImage mi2
                WHERE mi2.product = p
            )'
            )
            ->setParameter('locale', $locale)
            ->select('p.price, pt.title, pt.slug, ct.alias AS sub_cat, cpt.alias AS cat, mi.imageName AS image');
    }
}
