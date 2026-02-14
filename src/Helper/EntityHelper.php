<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Product;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class EntityHelper
{
    public const string LOCALE_DE = 'de';
    public const string LOCALE_EN = 'en';
    public const array LOCALES = [self::LOCALE_DE, self::LOCALE_EN];

    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Parent categories get a position, used for
     * sorting the top navigation menu.
     */
    /*
    public function setCategoryPosition(Category $category): void
    {
        $lastPosition = $this->em
            ->getRepository(Category::class)
            ->findLastCreatedParent();
        $nextPosition = ($lastPosition?->getPosition() ?? 0) + 1;

        if (!$category->getParent() instanceof Category) {
            $category->setPosition($nextPosition);
        }
    }
    */

    public function setCategoryAlias(Category $category): void
    {
        foreach ($category->getTranslations() as $translation) {
            $translation->setAlias($this->slugger->slug($translation->getName())->lower()->toString());
        }
    }

    public function setPageAlias(Page $page): void
    {
        $aliasDe = $this->slugger->slug($page->getTitleDe())->lower();
        $aliasEn = $this->slugger->slug($page->getTitleEn())->lower();
        $page->setAliasDe($aliasDe->toString());
        $page->setAliasEn($aliasEn->toString());
    }

    public function setProductTitleSlug(Product $product): void
    {
        $aliasDe = $this->slugger->slug($product->getTitleDe())->lower();
        $aliasEn = $this->slugger->slug($product->getTitleEn())->lower();
        $product->setTitleDeSlug($aliasDe->toString());
        $product->setTitleEnSlug($aliasEn->toString());
    }
}
