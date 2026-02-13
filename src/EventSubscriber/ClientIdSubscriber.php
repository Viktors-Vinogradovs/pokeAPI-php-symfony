<?php

namespace App\EventSubscriber;

use App\Repository\FavoriteRepository;
use App\Service\ClientIdResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ClientIdSubscriber implements EventSubscriberInterface
{
    private ?string $clientId = null;
    private bool $needsCookie = false;

    public function __construct(
        private readonly ClientIdResolver $clientIdResolver,
        private readonly FavoriteRepository $favoriteRepo,
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $this->clientId = $this->clientIdResolver->getClientId($request);
        $this->needsCookie = !$this->clientIdResolver->hasClientIdCookie($request);

        $request->attributes->set('client_id', $this->clientId);

        $favoritesCount = $this->favoriteRepo->countByClientId($this->clientId);
        $this->twig->addGlobal('favoritesCount', $favoritesCount);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $this->clientId === null || !$this->needsCookie) {
            return;
        }

        $this->clientIdResolver->ensureClientIdCookie($event->getResponse(), $this->clientId);
    }
}
