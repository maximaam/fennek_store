<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Tests\Trait\LoginUserTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    use LoginUserTrait;

    public function testIndexIsRedirect(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects();
    }

    public function testIndexIsLogged(): void
    {
        $client = self::createClient();
        $this->loginSuperAdmin($client);

        $client->request('GET', '/admin');
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Produkte Liste');
        self::assertSame('/admin/product', $client->getRequest()->getPathInfo());
    }
}
