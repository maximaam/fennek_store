<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Color;
use App\Helper\ProductHelper;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/cart', name: 'app_cart_')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'index', methods: [Request::METHOD_GET])]
    public function index(SessionInterface $session): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => ProductHelper::computeCard($session->get('cart', [])),
            'paypal_client_id' => $_ENV['PAYPAL_CLIENT_ID'],
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: [Request::METHOD_POST])]
    public function add(int $id, Request $request, SessionInterface $session, ProductRepository $productRepository, TranslatorInterface $translator): Response
    {
        $product = $productRepository->find($id)
            ?? throw $this->createNotFoundException('Product not found');

        $cart = $session->get('cart', []);
        $quantity = $request->request->getInt('quantity');
        $color = $request->request->get('color');
        $size = $request->request->get('size');
        $attachedMessage = $request->request->get('message');

        if ($quantity <= 0 || !\in_array($color, Color::values(), true)) {
            $this->addFlash(
                'error',
                $translator->trans('flashes.error_cart_params')
            );

            return $this->redirectToRoute('app_index_catalogue', [
                'catAlias' => $product->getCategory()->getParent(),
                'subCatAlias' => $product->getCategory(),
                'productId' => $product->getId(),
            ]);
        }

        $key = $id.'_'.$color.'_'.$size;
        $itemUrl = $this->generateUrl(
            'app_index_catalogue',
            [
                'catAlias' => $product->getCategory()->getParent()?->getAlias($request->getLocale()),
                'subCatAlias' => $product->getCategory()->getAlias($request->getLocale()),
                'productId' => $product->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cart[$key] = [
            'id' => $id,
            'title' => $product->getTitle($request->getLocale()),
            'item_number' => $product->getItemNumber(),
            'quantity' => $quantity,
            'color' => $color,
            'size' => $size,
            'price' => $product->getPrice(),
            'full_price' => $product->getPrice() * $quantity,
            'item_url' => $itemUrl,
            'image' => $product->getImages()[0]?->getImageName(),
            'message' => $attachedMessage,
        ];

        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{itemKey}', name: 'remove', methods: [Request::METHOD_GET])]
    public function remove(string $itemKey, SessionInterface $session): Response
    {
        $cart = $session->get('cart');
        unset($cart[$itemKey]);
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/_fragment/count-items', name: 'count_items', methods: ['GET'])]
    #[Cache(maxage: 0, public: false)]
    public function countItems(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);

        return $this->render('partials/_cart_fragment.html.twig', [
            'count' => \count($cart),
        ]);
    }
}
