<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findLastCreatedParent(): ?Category
    {
        return $this->findParentsQueryBuilder()
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findParentsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.id', 'DESC');
    }

    public function fetchChildren(?Category $category = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.parent IS NOT NULL');

        if ($category instanceof Category) {
            $qb->andWhere('c.parent = :parentId')
                ->setParameter('parentId', $category->getId());
        }

        $qb->orderBy('c.position', 'ASC');

        return $qb;
    }

    /**
     * @return Category[]
     */
    public function fetchForTopNavBar(int $limit = 5): array
    {
        return $this->findParentsQueryBuilder()
            ->setMaxResults($limit)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
