<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Helper\EntityHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntityHelper $entityHelper,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['onBeforeEntityPersisted'],
            BeforeEntityUpdatedEvent::class   => 'onBeforeEntityUpdated',
        ];
    }

    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (null === $entity) {
            return;
        }

        $this->entityHelper->setCategoryPosition($entity);
        $this->entityHelper->setCategoryAlias($entity);
    }

    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
       
    }
}
