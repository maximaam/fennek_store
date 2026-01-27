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
        if (!$event->isMainRequest()) {
            // return;
        }

        $request = $event->getRequest();

        // already has locale
        if ($request->attributes->has('_locale')) {
            return;
        }

        // ignore assets
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        $event->setResponse(
            new RedirectResponse('/'.$request->getDefaultLocale().$request->getPathInfo())
        );
    }
}
