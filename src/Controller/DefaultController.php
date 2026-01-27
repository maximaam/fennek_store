<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'app_default_')]
final class DefaultController extends AbstractController
{
    #[Route('/', name: 'index', methods: [Request::METHOD_GET])]
    public function index(Request $request): Response
    {
        $locale = $request->getPreferredLanguage(['de', 'en']) ?? 'de';

        return $this->redirectToRoute('app_index_index', ['_locale' => $locale], Response::HTTP_MOVED_PERMANENTLY);
    }
}
