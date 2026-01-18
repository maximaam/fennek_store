<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\MediaImage;
use App\Entity\Page;
use App\Entity\Product;
use App\Enum\MediaImageOwner;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Liip\ImagineBundle\Imagine\Cache\CacheManager as LiipCacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Service\FilterService as LiipFilterService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: MediaImage::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: MediaImage::class)]
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: MediaImage::class)]
// #[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: MediaImage::class)]
final readonly class MediaImageCacheListener
{
    public function __construct(
        private LiipCacheManager $cacheManager,
        private LiipFilterService $filterService,
        private FilterConfiguration $filterConfiguration,
        #[Autowire('%products_dir%')]
        private string $productsDir,
        #[Autowire('%pages_dir%')]
        private string $pagesDir,
    ) {
    }

    public function preRemove(MediaImage $image): void
    {
        $this->removeCachedImages($image);
    }

    public function postPersist(MediaImage $image): void
    {
        $this->createCacheImages($image);
    }

    public function prePersist(MediaImage $image): void
    {
        $this->assignOwner($image);
    }

    private function removeCachedImages(MediaImage $image): void
    {
        $path = $this->resolveMediaImagePath($image);
        $liipFilters = $this->getLiipFilters(\sprintf('%s_.', $image->getOwner()->value));
        foreach ($liipFilters as $filter) {
            $this->cacheManager->remove($path, $filter);
        }
    }

    private function createCacheImages(MediaImage $image): void
    {
        $path = $this->resolveMediaImagePath($image);
        $liipFilters = $this->getLiipFilters(\sprintf('%s_.', $image->getOwner()->value));
        foreach ($liipFilters as $filter) {
            $this->filterService->getUrlOfFilteredImage($path, $filter);
        }
    }

    private function resolveMediaImagePath(MediaImage $image): string
    {
        $dir = match ($image->getOwner()) {
            MediaImageOwner::PRODUCT => $this->productsDir,
            MediaImageOwner::PAGE => $this->pagesDir,
        };

        return \sprintf('%s/%s', $dir, $image->getImageName());
    }

    private function assignOwner(MediaImage $image): void
    {
        if ($image->getProduct() instanceof Product) {
            $image->setOwner(MediaImageOwner::PRODUCT);

            return;
        }

        if ($image->getPage() instanceof Page) {
            $image->setOwner(MediaImageOwner::PAGE);

            return;
        }

        throw new \LogicException('MediaImage must have an owner');
    }

    /** @return array<int, string> */
    private function getLiipFilters(string $prefix): array
    {
        $filters = $this->filterConfiguration->all();
        $filters = \preg_grep(\sprintf('/^%s/', $prefix), array_keys($filters));

        if (false === $filters) {
            return [];
        }

        return $filters;
    }
}
