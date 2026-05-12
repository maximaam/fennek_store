<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Helper\EntityHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture
{
    public const string CATEGORY_PARENT_1 = 'category-parent-1';
    public const string CATEGORY_CHILD_1 = 'category-child-1';

    public function load(ObjectManager $manager): void
    {
        $parent = new Category();
        $translationDe = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setName('Kategory Name Parent')
            ->setAlias('kategory-name-parent')
            ->setDescription('Kategory Beschreibung Parent');
        $translationEn = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_EN)
            ->setName('Category Name Parent')
            ->setAlias('category-name-parent')
            ->setDescription('Category Description Parent');
        $parent->addTranslation($translationDe);
        $parent->addTranslation($translationEn);

        $child = new Category()
            ->setParent($parent);
        $translationDe = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setName('Kategory Name Child')
            ->setAlias('kategory-name-child')
            ->setDescription('Kategory Beschreibung Child');
        $translationEn = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_EN)
            ->setName('Category Name Child')
            ->setAlias('category-name-child')
            ->setDescription('Category Description Child');
        $child->addTranslation($translationDe);
        $child->addTranslation($translationEn);

        $manager->persist($parent);
        $manager->persist($child);

        $manager->flush();

        $this->addReference(self::CATEGORY_PARENT_1, $parent);
        $this->addReference(self::CATEGORY_CHILD_1, $child);
    }
}
