<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * @return Category[]
     */
    public function fetchForTopNavBar(string $locale, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.translations', 't')
            ->addSelect('t')
            ->where('c.parent IS NULL')
            ->andWhere('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->setMaxResults($limit)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByLocale(string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.translations', 't', 'WITH', 't.locale = :locale')
            ->addSelect('t')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();
    }

    public function fetchOneBy(array $field): ?Category
    {
        return $this->createQueryBuilder('c')
            // ->leftJoin('c.translations', 't', 'WITH', \sprintf('t.%s = :target', key($field)))
            // ->addSelect('t')
            ->leftJoin('c.translations', 't')
            ->andWhere(\sprintf('t.%s = :target', key($field)))
            ->setParameter('target', current($field))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function fetchOneInDepthByAlias(string $alias, string $locale): ?Category
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT c, t, children')
            ->innerJoin('c.translations', 't')
            ->leftJoin('c.children', 'children')
            ->leftJoin('children.translations', 'ct')
            ->addSelect('ct')
            ->where('t.alias = :alias')
            ->andWhere('t.locale = :locale')
            ->setParameter('alias', $alias)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findChildrenIdsByAlias(string $alias, string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->select('children.id')
            ->innerJoin('c.translations', 't')
            ->innerJoin('c.children', 'children')
            ->where('t.alias = :alias')
            ->andWhere('t.locale = :locale')
            ->setParameter('alias', $alias)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getSingleColumnResult(); // Doctrine >= 2.8
    }
}
