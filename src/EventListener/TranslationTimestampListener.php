<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\CategoryTranslation;
use App\Entity\PageTranslation;
use App\Entity\ProductTranslation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Update the parent entity's updated_at field when a translation is updated.
 * Because Doctrine doesn't update the parent entity when a translation is updated.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class TranslationTimestampListener
{
    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $parent = match (true) {
                $entity instanceof CategoryTranslation => $entity->getCategory(),
                $entity instanceof ProductTranslation => $entity->getProduct(),
                $entity instanceof PageTranslation => $entity->getPage(),
                default => null,
            };

            if (null === $parent) {
                continue;
            }

            $parent->setUpdatedAt(new \DateTimeImmutable());

            $meta = $em->getClassMetadata($parent::class);

            $uow->recomputeSingleEntityChangeSet($meta, $parent);
        }
    }
}
