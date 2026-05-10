<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\Product;
use App\Entity\ProductTranslation;
use App\Helper\EntityHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CartControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private SluggerInterface $slugger;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $this->em = $em;

        /** @var SluggerInterface $slugger */
        $slugger = self::getContainer()->get(SluggerInterface::class);
        $this->slugger = $slugger;

        $this->cleanUp();
    }

    public function testIndexDisplaysCart(): void
    {
        $this->client->request('GET', '/de/cart/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    public function testAddValidProduct(): void
    {
        $product = $this->createProduct();
        $this->client->request('POST', '/de/cart/add/'.$product->getId(), [
            'quantity' => 2,
            'color' => 'red', // must exist in Color::values()
            'size' => 'L',
        ]);

        self::assertResponseRedirects('/de/cart/');

        $cart = CartHelper::getCartFromSession($this->client);
        $itemKey = \sprintf('%d_red_L', $product->getId());
        $locale = $this->client->getRequest()->getLocale();

        self::assertNotEmpty($cart);
        self::assertArrayHasKey($itemKey, $cart);
        self::assertSame($product->getId(), $cart[$itemKey]['id']);
        self::assertSame($product->getTitle($locale), $cart[$itemKey]['title']);
        self::assertSame(2, $cart[$itemKey]['quantity']);
        self::assertSame('red', $cart[$itemKey]['color']);
        self::assertSame('L', $cart[$itemKey]['size']);
        self::assertSame(1000, $cart[$itemKey]['price']);
        self::assertSame(2000, $cart[$itemKey]['full_price']);
        self::assertStringContainsString(
            \sprintf('/de/%s/%s', $product->getCategory()->getAlias($locale), $product->getTitleSlug($locale)),
            $cart[$itemKey]['item_url'],
        );
    }

    public function testAddInvalidProduct(): void
    {
        $product = $this->createProduct();
        $this->client->request('POST', '/de/cart/add/'.$product->getId(), [
            'quantity' => 0,
            'color' => '-not-exists',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorExists('.alert.alert-danger');
        self::assertSelectorTextContains('.alert.alert-danger', 'Fehler! Bitte korrigieren Sie Ihr Auswhal (Farbe, Größe, Menge...');
        self::assertSelectorTextContains('h1', $product->getTitle($this->client->getRequest()->getLocale()));
        self::assertSelectorExists('form');
    }

    public function testRemoveItem(): void
    {
        CartHelper::setSessionData($this->client, [
            'cart' => [
                '1_red_L' => [
                    'id' => 1,
                    'quantity' => 2,
                ],
            ],
        ]);

        $cart = CartHelper::getCartFromSession($this->client);
        self::assertArrayHasKey('1_red_L', $cart);

        $this->client->request('GET', '/de/cart/remove/1_red_L');
        self::assertResponseRedirects('/de/cart/');

        // HTTP request is stateless, so the session gets modified by the request
        $cart = CartHelper::getCartFromSession($this->client);
        self::assertEmpty($cart);
    }

    public function testCountItemsFragment(): void
    {
        CartHelper::setSessionData($this->client, [
            'cart' => ['a' => [], 'b' => []],
        ]);

        $this->client->request('GET', '/de/cart/_fragment/count-items');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.with-items', '2');
    }

    private function createProduct(): Product
    {
        $product = new Product()
            ->setPrice(1000)
            ->setCategory($this->createCategory(true, 'test category', 'test category'));
        $productTranslation = new ProductTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setTitle('Test product')
            ->setDescription('Test product description')
            ->setSlug('test-product');

        $product->addTranslation($productTranslation);

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    private function createCategory(bool $isParent, string $nameDe, string $nameEn, ?Category $parent = null): Category
    {
        $category = new Category();
        $translationDe = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setName($nameDe)
            ->setAlias($this->slugger->slug($nameDe)->toString());
        $translationEn = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_EN)
            ->setName($nameEn)
            ->setAlias($this->slugger->slug($nameEn)->toString());

        $category->addTranslation($translationDe);
        $category->addTranslation($translationEn);

        if (!$isParent && $parent instanceof Category) {
            $category->setParent($parent);
        }

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function cleanUp(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\ProductTranslation pt')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\CategoryTranslation ct')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();
    }
}
