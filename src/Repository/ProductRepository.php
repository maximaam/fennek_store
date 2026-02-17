<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function fetchForIndexPage(string $locale, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->innerJoin('p.translations', 'pt', 'WITH', 'pt.locale = :locale')
            ->innerJoin('c.translations', 'ct', 'WITH', 'ct.locale = :locale')
            ->leftJoin('c.parent', 'cp')
            ->leftJoin('cp.translations', 'cpt', 'WITH', 'cpt.locale = :locale')
            ->leftJoin( // Subquery ti get the first image of the product
                'p.images',
                'mi',
                'WITH',
                'mi.id = (
                SELECT MIN(mi2.id)
                FROM App\Entity\MediaImage mi2
                WHERE mi2.product = p
            )'
            )
            ->where('p.topItem = true')
            ->setParameter('locale', $locale)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->select('p.price, pt.title, pt.slug, ct.alias AS sub_cat, cpt.alias AS cat, mi.imageName AS image')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array<int, int|null> $categories
     */
    public function fetchByCategories(array $categories): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('c.id IN(:categories)')
            ->setParameter('categories', $categories);
    }

    /**
     * @return Product[]
     */
    public function searchTitle(string $query, string $locale, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.title'.\ucfirst($locale).' LIKE :title')
            ->setParameter('title', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
