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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/{_locale}/purchase', name: 'app_purchase_')]
final class PurchaseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NormalizerInterface&DenormalizerInterface $normalizer,
    ) {
    }

    #[Route('/create', name: 'create', methods: [Request::METHOD_POST])]
    public function create(PayPalClient $payPalClient, SessionInterface $session): RedirectResponse|JsonResponse
    {
        if ([] === $cart = $session->get('cart', [])) {
            return $this->redirectToRoute('app_index_index');
        }

        $cart = ProductHelper::computeCard($cart);
        $order = $payPalClient->createOrder($cart['totals']['total']);

        $payload = $this->normalizer->normalize($order);
        if (!\is_array($payload)) {
            throw new \LogicException('Normalized payment payload must be an array');
        }

        $purchase = new Purchase()
            ->setProduct($cart)
            ->setOrderId($order->id)
            ->setStatus(PayPalStatus::CREATED)
            ->setPayload($payload);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $this->json($order);
    }

    #[Route('/capture/{orderId}', name: 'capture', methods: [Request::METHOD_POST])]
    public function capture(PayPalClient $payPalClient, string $orderId): JsonResponse
    {
        $payment = $payPalClient->captureOrder($orderId);
        if (PayPalStatus::COMPLETED->value !== $payment->status) {
            throw new \LogicException('Payment status is not COMPLETED');
        }

        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]) ?? throw new \RuntimeException('No purchase found');

        $payload = $this->normalizer->normalize($payment);
        if (!\is_array($payload)) {
            throw new \LogicException('Normalized payment payload must be an array');
        }

        $purchase->setStatus(PayPalStatus::COMPLETED)
            ->setPayload($payload);
        $this->entityManager->flush();

        // TODO: persist payment status
        return $this->json($payment);
    }
}
