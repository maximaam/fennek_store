<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Tests\Trait\LoginUserTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    use LoginUserTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testIndexIsRedirect(): void
    {
        $this->client->request('GET', '/admin');
        self::assertResponseRedirects();
    }

    public function testIndexIsLogged(): void
    {
        $this->loginSuperAdmin($this->client);

        $this->client->request('GET', '/admin');
        self::assertResponseRedirects();

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Produkte Liste');
        self::assertSame('/admin/product', $this->client->getRequest()->getPathInfo());
    }
}
