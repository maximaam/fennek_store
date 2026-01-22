<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Purchase;
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

#[Route('/{_locale}/purchase', name: 'app_purchase_')]
final class PurchaseController extends AbstractController
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
        $order = $paypal->createOrder($cart['totals']['total']);

        $purchase = new Purchase()
            ->setProduct($cart)
            ->setOrderId($order['id'])
            ->setStatus(PayPalStatus::CREATED)
            ->setPayload($order);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $this->json($order);
    }

    #[Route('/capture/{orderId}', name: 'capture', methods: [Request::METHOD_POST])]
    public function capture(PayPalClient $paypal, string $orderId): JsonResponse
    {
        $payment = $paypal->captureOrder($orderId);

        if (PayPalStatus::COMPLETED->value !== $payment['status']) {
            throw new \LogicException('Payment status is not COMPLETED');
        }

        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]) ?? throw new \RuntimeException('No purchase found');

        $purchase->setStatus(PayPalStatus::COMPLETED)
            ->setPayload($payment);
        $this->entityManager->flush();

        // TODO: persist payment status
        return $this->json($payment);
    }
}
