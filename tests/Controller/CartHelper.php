<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;

final readonly class CartHelper
{
    public static function getCartFromSession(KernelBrowser $client): array
    {
        return $client->getSession()->get('cart') ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * Symfony test client simulates real HTTP:
     *
     * Each request is stateless
     * Session is linked via cookie
     * If you don’t attach the cookie → new empty session
     */
    public static function setSessionData(KernelBrowser $client, array $data): void
    {
        $session = $client->getSession();
        if (!$session) {
            return;
        }

        foreach ($data as $key => $value) {
            $session->set($key, $value);
        }

        // ✅ important, otherwise the session cookie is empty for the next request
        $session->save();

        // attach session cookie to the client for the next request
        $client->getCookieJar()->set(
            new Cookie($session?->getName(), $session->getId())
        );
    }
}
