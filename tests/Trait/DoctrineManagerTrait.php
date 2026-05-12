<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

trait DoctrineManagerTrait
{
    protected EntityManagerInterface $em;

    protected function initDoctrineManager(): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();

        $this->em = $em;
    }
}
