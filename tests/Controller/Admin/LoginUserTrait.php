<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

trait LoginUserTrait
{
    protected function loginAdmin(KernelBrowser $client): UserInterface
    {
        $container = self::getContainer();

        /** @var UserProviderInterface<UserInterface> $provider */
        $provider = $container->get('security.user.provider.concrete.admin_provider');

        $user = $provider->loadUserByIdentifier('admin');
        $client->loginUser($user);

        return $user;
    }

    protected function loginSuperAdmin(KernelBrowser $client): UserInterface
    {
        $container = self::getContainer();

        /** @var UserProviderInterface<UserInterface> $provider */
        $provider = $container->get('security.user.provider.concrete.admin_provider');

        $user = $provider->loadUserByIdentifier('sadmin');
        $client->loginUser($user);

        return $user;
    }
}
