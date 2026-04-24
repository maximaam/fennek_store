<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Entity\ProductTranslation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class IndexProductCacheInvalidator
{
    private bool $shouldInvalidate = false;

    public function __construct(
        #[Autowire(service: 'app.index_page_products')]
        private readonly TagAwareCacheInterface $cache
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        foreach ($entities as $entity) {
            if ($entity instanceof Product || $entity instanceof ProductTranslation) {
                $this->shouldInvalidate = true;
                break;
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postFlush(): void
    {
        if ($this->shouldInvalidate) {
            $this->cache->invalidateTags(['index_page_products']);
            $this->shouldInvalidate = false;
        }
    }
}
