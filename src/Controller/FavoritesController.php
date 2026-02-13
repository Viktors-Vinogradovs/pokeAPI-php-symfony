<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Repository\FavoriteRepository;
use App\Service\ClientIdResolver;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FavoritesController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites_index', methods: ['GET'])]
    public function index(Request $request, PokeApiClient $client, ClientIdResolver $clientIdResolver, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $clientIdResolver->getClientId($request);
        $favorites = $favoriteRepo->findNamesByClientId($clientId);

        $pokemonDetails = [];
        foreach ($favorites as $name) {
            try {
                $details = $client->getPokemonDetails($name);
                if ($details !== null) {
                    $pokemonDetails[] = $details;
                }
            } catch (PokeApiException) {
                // Skip pokemon that can't be loaded
            }
        }

        $response = $this->render('favorites/index.html.twig', [
            'pokemonList' => $pokemonDetails,
            'favorites' => $favorites,
            'favoritesCount' => count($favorites),
        ]);

        if (!$clientIdResolver->hasClientIdCookie($request)) {
            $clientIdResolver->ensureClientIdCookie($response, $clientId);
        }

        return $response;
    }

    #[Route('/favorites/toggle', name: 'app_favorites_toggle', methods: ['POST'])]
    public function toggle(Request $request, ClientIdResolver $clientIdResolver, FavoriteRepository $favoriteRepo): Response
    {
        $name = strtolower(trim($request->request->get('pokemon_name', '')));
        $token = $request->request->get('_token', '');

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('fav_' . $name, $token)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $clientId = $clientIdResolver->getClientId($request);

        if (!empty($name)) {
            $favorites = $favoriteRepo->findNamesByClientId($clientId);

            if (in_array($name, $favorites)) {
                $favoriteRepo->remove($clientId, $name);
            } else {
                $favoriteRepo->add($clientId, $name);
            }
        }

        // Redirect back to referer, or to favorites page
        $referer = $request->headers->get('referer');

        $response = $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('app_favorites_index');

        if (!$clientIdResolver->hasClientIdCookie($request)) {
            $clientIdResolver->ensureClientIdCookie($response, $clientId);
        }

        return $response;
    }
}
