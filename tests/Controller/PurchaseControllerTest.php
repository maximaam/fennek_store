<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Dto\EmailMessageDto;
use App\Dto\PayPal\OrderCaptureDto;
use App\Dto\PayPal\OrderDto;
use App\Entity\Purchase;
use App\Enum\PayPalStatus;
use App\Factory\EmailFactory;
use App\Helper\ProductHelper;
use App\Service\Mailer;
use App\Service\PayPalClient;
use Doctrine\ORM\EntityManagerInterface;use Symfony\Bridge\Doctrine\ManagerRegistry;use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Mime\Address;

final class PurchaseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $this->em = $em;

        $this->cleanUp();
    }

    public function testCreateWithEmptyCartRedirects(): void
    {
        $this->client->request('POST', '/de/purchase/create');
        self::assertResponseRedirects('/de/');
    }

    public function testCreateSuccess(): void
    {
        $container = $this->client->getContainer();

        // set cart in session for the coming request
        $this->setSessionData($this->client, [
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

        $cart = $this->client->getSession()->get('cart');
        $computedCart = ProductHelper::computeCard($cart);
        $expectedTotal = $computedCart['totals']['total'];
        self::assertEquals(2000, $expectedTotal);

        // mock PayPalClient
        $paypalMock = $this->createMock(PayPalClient::class);
        $paypalMock->expects(self::once())
            ->method('createOrder')
            ->with(self::equalTo($expectedTotal)) // Price is in cents
            ->willReturn(new OrderDto('ORDER123', PayPalStatus::CREATED->value, []));
        $container->set(PayPalClient::class, $paypalMock);

        $this->client->request('POST', '/de/purchase/create');
        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('ORDER123', $data['id']);

        // assert DB
        $purchase = $container->get('doctrine')
            ->getRepository(Purchase::class)
            ->findOneBy(['orderId' => 'ORDER123']);

        self::assertNotNull($purchase);
        self::assertArrayHasKey('1_red_L', $purchase->getProduct()['products']);

        $itemKey = $purchase->getProduct()['products']['1_red_L'];
        self::assertSame(PayPalStatus::CREATED, $purchase->getStatus());
        self::assertSame(2, $itemKey['quantity']);
        self::assertSame(2000, $itemKey['full_price']);
        self::assertSame(1000, $itemKey['price']);
        self::assertSame(2000, $purchase->getProduct()['totals']['total']);
        self::assertSame('red', $itemKey['color']);
        self::assertSame('L', $itemKey['size']);
        self::assertSame(1, $itemKey['id']);
    }

    public function testCaptureSuccess(): void
    {
        $container = $this->client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // create purchase
        $purchase = new Purchase()
            ->setOrderId('ORDER123')
            ->setStatus(PayPalStatus::CREATED);

        $em->persist($purchase);
        $em->flush();

        // mock PayPalClient
        $paypalMock = $this->createMock(PayPalClient::class);
        $captureDto = new OrderCaptureDto(
            'ORDER123',
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
            ->with(self::equalTo('ORDER123'))
            ->willReturn($captureDto);

        $container->set(PayPalClient::class, $paypalMock);

        $this->client->request('POST', '/de/purchase/capture/ORDER123');

        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('ORDER123', $data['id']);
        self::assertSame(PayPalStatus::COMPLETED->value, $data['status']);
        self::assertSame('9WHK9U3N3UU8J', $data['payer']['payer_id']);
        self::assertSame('amarezahi@chaabi.dz', $data['payer']['email_address']);
        self::assertSame('Amar Ezahi', $data['purchase_units'][0]['shipping']['name']['full_name']);
        self::assertSame('Rampe Vallée - Casbah', $data['purchase_units'][0]['shipping']['address']['address_line_1']);

        $purchase->setStatus(PayPalStatus::from($captureDto->status));
        $em->flush();
        $em->refresh($purchase);

        self::assertSame(PayPalStatus::COMPLETED, $purchase->getStatus());
        self::assertIsArray($purchase->getPayment());
    }

    public function testCaptureFailsIfNotCompleted(): void
    {
        $paypalMock = $this->createMock(PayPalClient::class);
        $paypalMock->expects(self::once())
            ->method('captureOrder')
            ->with(self::equalTo('ORDER123'))
            ->willReturn(new OrderCaptureDto('ORDER123', [], [], 'PENDING', [], []));

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

        $this->client->request('POST', '/de/purchase/capture/ORDER123');
    }

    public function testComplete(): void
    {
        $container = $this->client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // create purchase
        $purchase = new Purchase()
            ->setOrderId('ORDER123')
            ->setStatus(PayPalStatus::COMPLETED);

        $em->persist($purchase);
        $em->flush();

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

        $this->setSessionData($this->client, [
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
        $session = $this->client->getSession();
        self::assertNotEmpty($session->get('cart'));
        self::assertSame(1, $session->get('cart')['1_red_L']['id']);

        $this->client->request('GET', '/de/purchase/complete/ORDER123');
        self::assertResponseRedirects('/de/purchase/success/ORDER123');

        $session = $this->client->getSession();
        self::assertNull($session->get('cart'));
    }

    public function testSuccessPage(): void
    {
        $container = $this->client->getContainer();
        $em = $container->get('doctrine')->getManager();

        $purchase = new Purchase()
            ->setOrderId('ORDER123')
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

        $em->persist($purchase);
        $em->flush();

        $this->client->request('GET', '/de/purchase/success/ORDER123');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body');
    }

    /**
     * Symfony test client simulates real HTTP:
     *
     * Each request is stateless
     * Session is linked via cookie
     * If you don’t attach the cookie → new empty session
     */
    private function setSessionData(KernelBrowser $client, array $data): void
    {
        $session = $client->getSession();

        foreach ($data as $key => $value) {
            $session->set($key, $value);
        }

        // ✅ important, otherwise the session cookie is empty for the next request
        $session->save();

        // attach session cookie to the client for the next request
        $this->client->getCookieJar()->set(
            new Cookie($session->getName(), $session->getId())
        );
    }

    private function cleanUp(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Purchase p')->execute();
    }
}
