<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
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

    /**
     * @throws InvalidArgumentException
     */
    public function postFlush(): void
    {
        $this->cache->invalidateTags(['navbar_categories']);
    }
}
