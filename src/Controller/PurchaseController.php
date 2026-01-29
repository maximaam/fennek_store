<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Purchase;
use App\Enum\PayPalStatus;
use App\Factory\EmailFactory;
use App\Helper\ProductHelper;
use App\Service\Mailer;
use App\Service\PayPalClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        $this->entityManager->persist(
            new Purchase()
                ->setProduct($cart)
                ->setOrderId($order->id)
                ->setStatus(PayPalStatus::CREATED)
        );
        $this->entityManager->flush();

        return $this->json($order);
    }

    #[Route('/capture/{orderId}', name: 'capture', methods: [Request::METHOD_POST])]
    public function capture(PayPalClient $payPalClient, string $orderId): JsonResponse
    {
        $orderCapture = $payPalClient->captureOrder($orderId);
        if (PayPalStatus::COMPLETED->value !== $orderCapture->status) {
            throw new \LogicException('Payment status is not COMPLETED');
        }

        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]) ?? throw new \RuntimeException('No purchase found');

        $payment = $this->normalizer->normalize($orderCapture);
        if (!\is_array($payment)) {
            throw new \LogicException('Normalized payment payload must be an array');
        }

        $purchase->setStatus(PayPalStatus::COMPLETED)->setPayment($payment);
        $this->entityManager->flush();

        return $this->json($orderCapture);
    }

    #[Route('/complete/{orderId}', name: 'complete', methods: [Request::METHOD_GET])]
    public function complete(SessionInterface $session, Mailer $mailer, EmailFactory $emailFactory, string $orderId): RedirectResponse
    {
        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]) ?? throw new NotFoundHttpException('No purchase found');
        $this->addFlash('success', 'flashes.purchase_completed');
        $session->remove('cart');

        $email = $emailFactory->purchaseSuccess($purchase);
        $mailer->send($email);

        return $this->redirectToRoute('app_purchase_success', ['orderId' => $orderId]);
    }

    #[Route('/success/{orderId}', name: 'success', methods: [Request::METHOD_GET])]
    public function success(string $orderId): Response
    {
        $purchase = $this->entityManager->getRepository(Purchase::class)
            ->findOneBy(['orderId' => $orderId]) ?? throw new NotFoundHttpException('No purchase found');

        return $this->render('purchase/success.html.twig', [
            'purchase' => $purchase,
        ]);
    }
}
