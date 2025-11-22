<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Category;
use App\Entity\Page;
use App\Helper\EntityHelper;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityHelper $entityHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['onBeforeEntityPersisted'],
            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdated',
        ];
    }

    /**
     * @param BeforeEntityPersistedEvent<object> $event
     */
    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Category) {
            $this->entityHelper->setCategoryPosition($entity);
            $this->entityHelper->setCategoryAlias($entity);
        }

        if ($entity instanceof Page) {
            $this->entityHelper->setPageAlias($entity);
        }
    }

    /**
     * @param BeforeEntityUpdatedEvent<object> $event
     */
    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
    }
}
