<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\PurchaseFixtures;
use App\Dto\EmailMessageDto;
use App\Dto\PayPal\OrderCaptureDto;
use App\Dto\PayPal\OrderDto;
use App\Entity\Purchase;
use App\Enum\PayPalStatus;
use App\Factory\EmailFactory;
use App\Helper\ProductHelper;
use App\Service\Mailer;
use App\Service\PayPalClient;
use App\Tests\Trait\DoctrineManagerTrait;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Address;

final class PurchaseControllerTest extends WebTestCase
{
    use DoctrineManagerTrait;
    public const string BASE_URL = '/de/purchase';

    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $this->initDoctrineManager();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $databaseTool = $databaseToolCollection->get();
        $this->databaseTool = $databaseTool;
        $this->databaseTool->loadFixtures([PurchaseFixtures::class]);
    }

    public function testCreateWithEmptyCartRedirects(): void
    {
        $this->client->request('POST', self::BASE_URL.'/create');
        self::assertResponseRedirects('/de/');
    }

    public function testCreateSuccess(): void
    {
        $container = $this->client->getContainer();

        // set the cart in session for the coming request
        CartHelper::setSessionData($this->client, [
            'cart' => [
                '1_red_L' => [
                    'id' => 1,
                    'quantity' => 2,
                    'color' => 'red',
                    'size' => 'L',
                    'price' => 1000,
                    'full_price' => 2000,
                ],
            ],
        ]);

        $cart = CartHelper::getCartFromSession($this->client);
        $computedCart = ProductHelper::computeCard($cart);
        $expectedTotal = $computedCart['totals']['total'];
        self::assertEquals(2000, $expectedTotal);

        $orderId = uniqid('ORDER', true);
        $paypalMock = $this->createMock(PayPalClient::class);
        $paypalMock->expects(self::once())
            ->method('createOrder')
            ->with(self::equalTo($expectedTotal)) // Price is in cents
            ->willReturn(new OrderDto($orderId, PayPalStatus::CREATED->value, []));
        $container->set(PayPalClient::class, $paypalMock);

        $this->client->request('POST', self::BASE_URL.'/create');
        self::assertResponseIsSuccessful();

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($orderId, $data['id']);

        $purchase = $this->em
            ->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]);

        self::assertInstanceOf(Purchase::class, $purchase);
        self::assertArrayHasKey('1_red_L', $purchase->getProduct()['products']);

        $itemKey = $purchase->getProduct()['products']['1_red_L'];
        self::assertSame(PayPalStatus::CREATED, $purchase->getStatus());
        self::assertSame(2, $itemKey['quantity']);
        self::assertSame(2000, $itemKey['full_price']);
        self::assertSame(1000, $itemKey['price']);
        self::assertSame(2000, $purchase->getProduct()['totals']['total']);
        self::assertSame('red', $itemKey['color']);
        self::assertSame('L', $itemKey['size']);
    }

    public function testCaptureSuccess(): void
    {
        $container = $this->client->getContainer();
        $purchase = $this->getPurchase();
        $orderId = $purchase->getOrderId();

        $paypalMock = $this->createMock(PayPalClient::class);
        $captureDto = new OrderCaptureDto(
            $orderId,
            [],
            [
                'name' => [
                    'given_name' => 'Amar',
                    'surname' => 'Ezahi',
                ],
                'address' => [
                    'country_code' => 'DZ',
                ],
                'email_address' => 'amarezahi@chaabi.dz',
                'payer_id' => '9WHK9U3N3UU8J',
            ],
            PayPalStatus::COMPLETED->value,
            [
                'paypal' => [
                    'name' => [
                        'given_name' => 'Amar',
                        'surname' => 'Ezahi',
                    ],
                    'address' => [
                        'country_code' => 'DZ',
                    ],
                    'account_id' => '9WHK9U3N3UU8J',
                    'email_address' => 'amarezahi@chaabi.dz',
                    'account_status' => 'VERIFIED',
                ],
            ],
            [
                [
                    'payments' => [
                        'captures' => [
                            [
                                'id' => 'PAYID-MAURBFY9MY85524G73920256',
                                'links' => [],
                                'amount' => [
                                    'value' => '11.99',
                                    'currency_code' => 'EUR',
                                ],
                                'status' => 'COMPLETED',
                                'update_time' => '2021-02-14T11:59:19Z',
                                'created_time' => '2021-02-14T11:59:19Z',
                                'final_capture' => true,
                            ],
                        ],
                    ],
                    'shipping' => [
                        'name' => [
                            'full_name' => 'Amar Ezahi',
                        ],
                        'address' => [
                            'postal_code' => '16000',
                            'admin_area_1' => '',
                            'admin_area_2' => 'Alger',
                            'country_code' => 'DZ',
                            'address_line_1' => 'Rampe Vallée - Casbah',
                        ],
                    ],
                ],
            ],
        );

        $paypalMock->expects(self::once())
            ->method('captureOrder')
            ->with(self::equalTo($orderId))
            ->willReturn($captureDto);
        $container->set(PayPalClient::class, $paypalMock);

        $this->client->request('POST', self::BASE_URL.'/capture/'.$orderId);
        self::assertResponseIsSuccessful();

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame($orderId, $data['id']);
        self::assertSame(PayPalStatus::COMPLETED->value, $data['status']);
        self::assertSame('9WHK9U3N3UU8J', $data['payer']['payer_id']);
        self::assertSame('amarezahi@chaabi.dz', $data['payer']['email_address']);
        self::assertSame('Amar Ezahi', $data['purchase_units'][0]['shipping']['name']['full_name']);
        self::assertSame('Rampe Vallée - Casbah', $data['purchase_units'][0]['shipping']['address']['address_line_1']);

        $purchase->setStatus(PayPalStatus::from($captureDto->status));
        $this->em->flush();
        $this->em->refresh($purchase);

        self::assertSame(PayPalStatus::COMPLETED, $purchase->getStatus());
    }

    public function testCaptureFailsIfNotCompleted(): void
    {
        $purchase = $this->getPurchase();
        $orderId = $purchase->getOrderId();

        $paypalMock = $this->createMock(PayPalClient::class);
        $paypalMock->expects(self::once())
            ->method('captureOrder')
            ->with(self::equalTo($orderId))
            ->willReturn(new OrderCaptureDto($orderId, [], [], 'PENDING', [], []));
        $this->client->getContainer()->set(PayPalClient::class, $paypalMock);

        /**
         * In Symfony functional tests, exceptions are converted into HTTP responses by the kernel,
         * so the exception never reaches PHPUnit.
         * So we need to disable exceptions handling in the client, otherwise use the response:
         * $exception = $this->client->getResponse()->getContent(false);
         * self::assertStringContainsString('LogicException', $exception);
         * self::assertResponseStatusCodeSame(500);.
         */
        $this->client->catchExceptions(false);

        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Payment status is not COMPLETED');

        $this->client->request('POST', self::BASE_URL.'/capture/'.$orderId);
    }

    public function testComplete(): void
    {
        $container = $this->client->getContainer();
        $purchase = $this->getPurchase();
        $purchase->setStatus(PayPalStatus::COMPLETED);
        $orderId = $purchase->getOrderId();

        // mock mailer
        $mailerMock = $this->createMock(Mailer::class);
        $mailerMock->expects(self::once())->method('send');

        $container->set(Mailer::class, $mailerMock);

        // mock email factory
        $emailFactoryMock = $this->createMock(EmailFactory::class);
        $emailFactoryMock->expects(self::once())
            ->method('purchaseSuccess')
            ->willReturn(new EmailMessageDto(new Address('test@gmail.com'), 'test subject', 'test body'));

        $container->set(EmailFactory::class, $emailFactoryMock);

        CartHelper::setSessionData($this->client, [
            'cart' => [
                '1_red_L' => [
                    'id' => 1,
                    'quantity' => 2,
                    'color' => 'red',
                    'size' => 'L',
                    'price' => 1000,
                    'full_price' => 2000,
                ],
            ],
        ]);
        $cart = CartHelper::getCartFromSession($this->client);
        self::assertNotEmpty($cart);
        self::assertSame(1, $cart['1_red_L']['id']);

        $this->client->request('GET', self::BASE_URL.'/complete/'.$orderId);
        self::assertResponseRedirects(self::BASE_URL.'/success/'.$orderId);

        self::assertEmpty(CartHelper::getCartFromSession($this->client));
    }

    public function testSuccessPage(): void
    {
        $purchase = $this->getPurchase()
            ->setStatus(PayPalStatus::COMPLETED)
            ->setProduct([
                'totals' => [
                    'excl_tax' => 2000,
                    'vat' => 200,
                    'total' => 2200,
                ],
                'products' => [
                    '1_red_L' => [
                        'id' => 1,
                        'quantity' => 2,
                        'color' => 'red',
                        'size' => 'L',
                        'price' => 1000,
                        'full_price' => 2000,
                        'message' => 'test message',
                    ],
                ],
            ]);

        $this->client->request('GET', self::BASE_URL.'/success/'.$purchase->getOrderId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    /**
     * Get any purchase from the fixtures.
     */
    private function getPurchase(): Purchase
    {
        return $this->em->getRepository(Purchase::class)->findOneBy([])
            ?? throw new \RuntimeException('No purchase found');
    }
}
