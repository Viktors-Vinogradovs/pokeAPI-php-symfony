<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Repository\FavoriteRepository;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FavoritesController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites_index', methods: ['GET'])]
    public function index(Request $request, PokeApiClient $client, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $request->attributes->get('client_id');
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

        return $this->render('favorites/index.html.twig', [
            'pokemonList' => $pokemonDetails,
            'favorites' => $favorites,
        ]);
    }

    #[Route('/favorites/toggle', name: 'app_favorites_toggle', methods: ['POST'])]
    public function toggle(Request $request, FavoriteRepository $favoriteRepo): Response
    {
        $name = strtolower(trim($request->request->get('pokemon_name', '')));
        $token = $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('fav_' . $name, $token)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        if (strlen($name) > 100) {
            throw $this->createNotFoundException('Invalid pokemon name.');
        }

        $clientId = $request->attributes->get('client_id');

        if (!empty($name)) {
            if ($favoriteRepo->exists($clientId, $name)) {
                $favoriteRepo->remove($clientId, $name);
            } else {
                $favoriteRepo->add($clientId, $name);
            }
        }

        $referer = $request->headers->get('referer');
        $appHost = $request->getSchemeAndHttpHost();

        if ($referer && str_starts_with($referer, $appHost)) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_favorites_index');
    }
}
