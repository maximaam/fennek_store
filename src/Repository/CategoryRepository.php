<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
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

    /**
     * @return array<mixed, mixed>
     */
    public function fetchForTopNavBar(string $locale, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.translations', 't')
            ->where('c.parent IS NULL')
            ->andWhere('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->setMaxResults($limit)
            ->orderBy('c.position', 'ASC')
            ->select('t.name, t.alias, c.id')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array<string, mixed> $field
     */
    public function fetchOneBy(array $field, string $locale): ?Category
    {
        return $this->baseQuery($locale)
            ->andWhere(\sprintf('ct.%s = :target', key($field)))
            ->setParameter('target', current($field))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function fetchOneByAlias(string $alias, string $locale): ?Category
    {
        return $this->baseQuery($locale)
            ->andWhere('ct.alias = :alias')
            ->setParameter('alias', $alias)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<mixed, mixed>
     */
    public function fetchOneFlatByAlias(string $alias, string $locale): array
    {
        return $this->baseQuery($locale)
            ->leftJoin('c.children', 'child')
            ->andWhere('ct.alias = :alias')
            ->setParameter('alias', $alias)
            ->select('
                c.id,
                c.updatedAt,
                ct.alias,
                ct.name,
                ct.description,
                IDENTITY(c.parent) AS parent_id,
                CASE WHEN COUNT(child.id) > 0 THEN true ELSE false END AS is_parent
            ')
            ->groupBy('c.id, ct.alias, ct.name, ct.description')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @return array<mixed, mixed>
     */
    public function fetchSubCategoriesByParentId(int $id, string $locale): array
    {
        return $this->baseQuery($locale)
            ->select('c.id, ct.alias, ct.name')
            ->where('c.parent = :parentId')
            ->setParameter('parentId', $id)
            ->getQuery()
            ->getArrayResult();
    }

    private function baseQuery(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.translations', 'ct', 'WITH', 'ct.locale = :locale')
            ->andWhere('ct.locale = :locale')
            ->setParameter('locale', $locale);
    }
}
