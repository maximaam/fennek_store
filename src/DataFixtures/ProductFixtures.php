<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\Product;
use App\Entity\ProductTranslation;
use App\Helper\EntityHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture
{
    public const string PRODUCT_1 = 'product-1';

    public function load(ObjectManager $manager): void
    {
        $category = new Category();

        $categoryTranslation = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setName('Test category')
            ->setAlias('test-category');

        $category->addTranslation($categoryTranslation);

        $product = new Product()
            ->setPrice(1000)
            ->setCategory($category);

        $translationDe = new ProductTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setTitle('Test Produkt')
            ->setDescription('Beschreibung vom Produkt')
            ->setSlug('test-produkt');
        $translationEn = new ProductTranslation()
            ->setLocale(EntityHelper::LOCALE_EN)
            ->setTitle('Test product')
            ->setDescription('Description of the test product')
            ->setSlug('test-product');

        $product->addTranslation($translationDe);
        $product->addTranslation($translationEn);

        $manager->persist($category);
        $manager->persist($product);

        $manager->flush();

        $this->addReference(self::PRODUCT_1, $product);
    }
}
