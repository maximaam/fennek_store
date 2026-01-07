<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/cart', name: 'app_cart_')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'index', methods: [Request::METHOD_GET])]
    public function index(SessionInterface $session): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $session->get('cart', []),
        ]);
    }
}
