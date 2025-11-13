<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class EntityHelper
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Parent categories get a position, used for
     * sorting the top navigation menu.
     */
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

    public function setCategoryAlias(Category $category): void
    {
        $aliasDe = $this->slugger->slug($category->getNameDe())->lower();
        $aliasEn = $this->slugger->slug($category->getNameEn())->lower();

        $category->setAliasDe($aliasDe->toString());
        $category->setAliasEn($aliasEn->toString());
    }
}
