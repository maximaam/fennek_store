<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\Page;
use App\Entity\Product;
use App\Entity\ProductTranslation;
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

    public function setCategoryAlias(Category $category): void
    {
        /** @var CategoryTranslation $translation */
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
        /** @var ProductTranslation $translation */
        foreach ($product->getTranslations() as $translation) {
            $translation->setSlug($this->slugger->slug($translation->getTitle())->lower()->toString());
        }
    }

    /**
     * @return array<int, int>
     */
    public static function getCategoryChildrenIds(Category $category): array
    {
        return \array_map(static fn (Category $cat) => $cat->getId(), \iterator_to_array($category->getChildren()));
    }
}
