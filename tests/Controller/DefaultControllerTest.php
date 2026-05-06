<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame('/de/', $client->getRequest()->getPathInfo());
    }
}
