<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Repository\FavoriteRepository;
use App\Service\ClientIdResolver;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PokemonController extends AbstractController
{
    private const PER_PAGE = 15;

    #[Route('/pokemon', name: 'app_pokemon_list', methods: ['GET'])]
    public function list(Request $request, PokeApiClient $client, ClientIdResolver $clientIdResolver, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $clientIdResolver->getClientId($request);
        $favorites = $favoriteRepo->findNamesByClientId($clientId);
        $needsCookie = !$clientIdResolver->hasClientIdCookie($request);

        try {
            $searchTerm = trim(strtolower($request->query->get('q', '')));
            $selectedTypes = array_filter($request->query->all('types') ?? []);
            $page = max(1, (int) $request->query->get('page', 1));

            $allTypes = $client->getTypes();
            $selectedTypes = array_values(array_intersect($selectedTypes, $allTypes));

            $filteredMode = $searchTerm !== '' || !empty($selectedTypes);

            if (!$filteredMode) {
                $offset = ($page - 1) * self::PER_PAGE;
                $pageData = $client->getPokemonPage(self::PER_PAGE, $offset);
                $totalItems = (int) $pageData['count'];
                $totalPages = $totalItems > 0 ? max(1, (int) ceil($totalItems / self::PER_PAGE)) : 1;
                $page = min($page, $totalPages);

                if ($page > 1 && $offset !== ($page - 1) * self::PER_PAGE) {
                    $offset = ($page - 1) * self::PER_PAGE;
                    $pageData = $client->getPokemonPage(self::PER_PAGE, $offset);
                }

                $pokemonNames = $pageData['results'];
            } else {
                if (!empty($selectedTypes)) {
                    $typeLists = [];
                    foreach ($selectedTypes as $type) {
                        $typeLists[] = $client->getPokemonListByType($type);
                    }
                    $baseList = count($typeLists) > 1
                        ? array_intersect(...$typeLists)
                        : $typeLists[0];
                } else {
                    $pageData = $client->getPokemonPage(2000, 0);
                    $baseList = $pageData['results'];
                }

                if ($searchTerm !== '') {
                    $baseList = array_filter($baseList, function ($name) use ($searchTerm) {
                        return str_contains(strtolower($name), $searchTerm);
                    });
                }

                $filteredNames = array_values($baseList);
                $totalItems = count($filteredNames);
                $totalPages = $totalItems > 0 ? max(1, (int) ceil($totalItems / self::PER_PAGE)) : 1;
                $page = min($page, $totalPages);

                $offset = ($page - 1) * self::PER_PAGE;
                $pokemonNames = array_slice($filteredNames, $offset, self::PER_PAGE);
            }

            $pokemonDetails = [];
            foreach ($pokemonNames as $name) {
                $details = $client->getPokemonDetails($name);
                if ($details !== null) {
                    $pokemonDetails[] = $details;
                }
            }

            $response = $this->render('pokemon/list.html.twig', [
                'pokemonList' => $pokemonDetails,
                'searchTerm' => $searchTerm,
                'selectedTypes' => $selectedTypes,
                'allTypes' => $allTypes,
                'page' => $page,
                'totalPages' => $totalPages,
                'hasResults' => !empty($pokemonDetails),
                'favorites' => $favorites,
                'favoritesCount' => count($favorites),
            ]);
        } catch (PokeApiException $e) {
            $response = $this->render('pokemon/list.html.twig', [
                'error' => $e->getMessage(),
                'pokemonList' => [],
                'searchTerm' => '',
                'selectedTypes' => [],
                'allTypes' => [],
                'page' => 1,
                'totalPages' => 1,
                'hasResults' => false,
                'favorites' => $favorites,
                'favoritesCount' => count($favorites),
            ]);
        }

        if ($needsCookie) {
            $clientIdResolver->ensureClientIdCookie($response, $clientId);
        }

        return $response;
    }

    #[Route('/pokemon/{name}', name: 'app_pokemon_show', methods: ['GET'])]
    public function show(string $name, Request $request, PokeApiClient $client, ClientIdResolver $clientIdResolver, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $clientIdResolver->getClientId($request);
        $favorites = $favoriteRepo->findNamesByClientId($clientId);

        try {
            $details = $client->getPokemonDetails($name);
        } catch (PokeApiException $e) {
            throw new NotFoundHttpException('Unable to load Pokemon data.');
        }

        if ($details === null) {
            throw new NotFoundHttpException("Pokemon '{$name}' not found.");
        }

        $response = $this->render('pokemon/show.html.twig', [
            'pokemon' => $details,
            'isFavorite' => in_array($name, $favorites),
            'favorites' => $favorites,
            'favoritesCount' => count($favorites),
        ]);

        if (!$clientIdResolver->hasClientIdCookie($request)) {
            $clientIdResolver->ensureClientIdCookie($response, $clientId);
        }

        return $response;
    }
}
