<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Product::class)]
final readonly class ProductChangedListener
{
    /**
     * Always clears cache on update.
     */
    public function preUpdate(): void
    {
        // $this->removeCachedImages($product->getImages());
    }

    public function preRemove(): void
    {
        // $this->removeCachedImages($product->getImages());
    }
}
