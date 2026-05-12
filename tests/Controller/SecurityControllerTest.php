<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Controller\Admin\LoginUserTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    use LoginUserTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testLoginSuccessful(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('login', [
            '_username' => $_ENV['LOGIN_USERNAME'],
            '_password' => $_ENV['LOGIN_PASSWORD'],
        ]);

        self::assertResponseRedirects('/admin');
    }

    public function testLogoutRedirects(): void
    {
        $this->loginSuperAdmin($this->client);
        $this->client->request('GET', '/logout');

        self::assertResponseRedirects();
    }
}
