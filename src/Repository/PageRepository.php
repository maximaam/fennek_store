<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function fetchOneByAlias(string $alias, string $locale): ?array
    {
        return $this->baseQuery($locale)
            ->andWhere('pt.alias = :alias')
            ->setParameter('alias', $alias)
            ->addSelect('p, pt, pi')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    private function baseQuery(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.translations', 'pt', 'WITH', 'pt.locale = :locale')
            ->leftJoin('p.images', 'pi')
            ->andWhere('pt.locale = :locale')
            ->setParameter('locale', $locale);
    }
}
