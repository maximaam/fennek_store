<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleRedirectSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ignore sub-requests and all URLs but the homepage
        if (!$event->isMainRequest() || \DIRECTORY_SEPARATOR !== $request->getPathInfo()) {
            return;
        }

        if ($request->attributes->has('_locale')) {
            return;
        }

        $event->setResponse(
            new RedirectResponse(\DIRECTORY_SEPARATOR.$request->getDefaultLocale().$request->getPathInfo())
        );
    }
}
