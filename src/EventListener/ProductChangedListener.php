<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\MediaImage;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Events;
use Liip\ImagineBundle\Imagine\Cache\CacheManager as LiipCacheManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemoveImage', entity: MediaImage::class)]
final readonly class ProductChangedListener
{
    public function __construct(
        private LiipCacheManager $lcm,
        #[Autowire('%products_dir%')]
        private string $productsDir,
    ) {
    }

    /**
     * Always clears cache on update.
     */
    public function preUpdate(Product $product): void
    {
        $this->removeCachedImages($product->getImages());
    }

    public function preRemove(Product $product): void
    {
        $this->removeCachedImages($product->getImages());
    }

    public function preRemoveImage(MediaImage $image): void
    {
        $this->removeCachedImages($image);
    }

    /**
     * @param MediaImage|Collection<int,MediaImage> $images
     */
    private function removeCachedImages(MediaImage|Collection $images): void
    {
        $images = $images instanceof Collection ? $images : [$images];

        if (0 === \count($images)) {
            return;
        }

        foreach ($images as $image) {
            $this->lcm->remove(\sprintf('%s/%s', $this->productsDir, $image->getImageName()));
        }

        // $this->lcm->remove(\sprintf('%s/%s', $this->productsDir, $image));
        // $this->lcm->remove(\sprintf('%s/%s', $this->postsDir, $image.'.webp'));
    }
}
