<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::postFlush)]
final readonly class NavbarCacheInvalidator
{
    public function __construct(
        #[Autowire(service: 'app.navbar_categories')]
        private TagAwareCacheInterface $cache
    ) {
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->cache->invalidateTags(['navbar_categories']);
    }
}
