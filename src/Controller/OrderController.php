<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Enum\PayPalStatus;
use App\Helper\ProductHelper;
use App\Service\PayPalClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/order', name: 'app_order_')]
final class OrderController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/create', name: 'create', methods: [Request::METHOD_POST])]
    public function create(PayPalClient $paypal, SessionInterface $session): RedirectResponse|JsonResponse
    {
        if ([] === $cart = $session->get('cart', [])) {
            return $this->redirectToRoute('app_index_index');
        }

        $cart = ProductHelper::computeCard($cart);
        $orderRequest = $paypal->createOrder($cart['totals']['total']);

        $order = new Order()
            ->setProduct($cart)
            ->setOrderId($orderRequest['id'])
            ->setStatus(PayPalStatus::CREATED)
            ->setPayload($orderRequest);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $this->json($orderRequest);
    }

    #[Route('/capture/{orderId}', name: 'capture', methods: [Request::METHOD_POST])]
    public function capture(PayPalClient $paypal, string $orderId): JsonResponse
    {
        $payment = $paypal->captureOrder($orderId);

        if (PayPalStatus::COMPLETED->value !== $payment['status']) {
            throw new \LogicException('Payment status is not COMPLETED');
        }

        $order = $this->entityManager->getRepository(Order::class)
            ->findOneBy(['orderId' => $orderId]);

        $order->setStatus(PayPalStatus::COMPLETED)
            ->setPayload($payment);
        $this->entityManager->flush();

        // TODO: persist payment status
        return $this->json($payment);
    }
}
