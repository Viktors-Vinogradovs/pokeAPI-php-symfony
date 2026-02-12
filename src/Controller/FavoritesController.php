<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FavoritesController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorites_index', methods: ['GET'])]
    public function index(SessionInterface $session, PokeApiClient $client): Response
    {
        $favorites = $session->get('favorites', []);

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
            'favoritesCount' => count($favorites),
        ]);
    }

    #[Route('/favorites/toggle', name: 'app_favorites_toggle', methods: ['POST'])]
    public function toggle(Request $request, SessionInterface $session): Response
    {
        $name = strtolower(trim($request->request->get('pokemon_name', '')));
        $token = $request->request->get('_token', '');

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('fav_' . $name, $token)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        if (!empty($name)) {
            $favorites = $session->get('favorites', []);

            $key = array_search($name, $favorites);
            if ($key !== false) {
                // Remove from favorites
                unset($favorites[$key]);
                $favorites = array_values($favorites); // Re-index
            } else {
                // Add to favorites (avoid duplicates)
                if (!in_array($name, $favorites)) {
                    $favorites[] = $name;
                }
            }

            $session->set('favorites', $favorites);
        }

        // Redirect back to referer, or to favorites page
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_favorites_index');
    }
}
