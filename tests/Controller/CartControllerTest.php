<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\ProductFixtures;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
{
    public const string BASE_URL = '/de/cart';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->databaseTool->loadFixtures([ProductFixtures::class]);
    }

    public function testIndexDisplaysCart(): void
    {
        $this->client->request('GET', self::BASE_URL.'/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    public function testAddValidProduct(): void
    {
        /** @var Product $product */
        $product = $this->em
            ->getRepository(Product::class)
            ->findOneBy([]);

        $this->client->request('POST', self::BASE_URL.'/add/'.$product->getId(), [
            'quantity' => 2,
            'color' => 'red', // must exist in Color::values()
            'size' => 'L',
        ]);

        self::assertResponseRedirects(self::BASE_URL.'/');

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
        /** @var Product $product */
        $product = $this->em
            ->getRepository(Product::class)
            ->findOneBy([]);

        $this->client->request('POST', self::BASE_URL.'/add/'.$product->getId(), [
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

        $this->client->request('GET', self::BASE_URL.'/remove/1_red_L');
        self::assertResponseRedirects(self::BASE_URL.'/');

        // HTTP request is stateless, so the session gets modified by the request
        $cart = CartHelper::getCartFromSession($this->client);
        self::assertEmpty($cart);
    }

    public function testCountItemsFragment(): void
    {
        CartHelper::setSessionData($this->client, [
            'cart' => ['a' => [], 'b' => []],
        ]);

        $this->client->request('GET', self::BASE_URL.'/_fragment/count-items');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.with-items', '2');
    }
}
