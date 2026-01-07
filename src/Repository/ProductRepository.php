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

    public function fetchByCategories(array $categories): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->where('c.id IN(:categories)')
            ->setParameter('categories', $categories);
    }

    public function searchTitle(string $query, string $locale, int $limit = 10)
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
