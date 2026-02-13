<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ClientIdResolver
{
    private const COOKIE_NAME = 'client_id';
    private const COOKIE_LIFETIME_DAYS = 180;

    public function getClientId(Request $request): string
    {
        $clientId = $request->cookies->get(self::COOKIE_NAME);

        if ($clientId !== null && $clientId !== '' && self::isValidUuid($clientId)) {
            return $clientId;
        }

        return Uuid::v4()->toRfc4122();
    }

    public function ensureClientIdCookie(Response $response, string $clientId): void
    {
        $response->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue($clientId)
                ->withExpires(new \DateTimeImmutable('+' . self::COOKIE_LIFETIME_DAYS . ' days'))
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );
    }

    public function hasClientIdCookie(Request $request): bool
    {
        $clientId = $request->cookies->get(self::COOKIE_NAME);

        return $clientId !== null && $clientId !== '' && self::isValidUuid($clientId);
    }

    private static function isValidUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }
}
