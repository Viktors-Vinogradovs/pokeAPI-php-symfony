<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Repository\FavoriteRepository;
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
    public function list(Request $request, PokeApiClient $client, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $request->attributes->get('client_id');
        $favorites = $favoriteRepo->findNamesByClientId($clientId);

        try {
            $searchTerm = trim(strtolower($request->query->get('q', '')));
            $selectedTypes = array_filter($request->query->all('types') ?? []);
            $page = max(1, (int) $request->query->get('page', 1));

            $allTypes = $client->getTypes();
            $selectedTypes = array_values(array_intersect($selectedTypes, $allTypes));

            $result = $client->getFilteredPokemonPage($searchTerm, $selectedTypes, $page, self::PER_PAGE);

            return $this->render('pokemon/list.html.twig', [
                'pokemonList' => $result['pokemonDetails'],
                'searchTerm' => $searchTerm,
                'selectedTypes' => $selectedTypes,
                'allTypes' => $allTypes,
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'hasResults' => !empty($result['pokemonDetails']),
                'favorites' => $favorites,
            ]);
        } catch (PokeApiException $e) {
            return $this->render('pokemon/list.html.twig', [
                'error' => $e->getMessage(),
                'pokemonList' => [],
                'searchTerm' => '',
                'selectedTypes' => [],
                'allTypes' => [],
                'page' => 1,
                'totalPages' => 1,
                'hasResults' => false,
                'favorites' => $favorites,
            ]);
        }
    }

    #[Route('/pokemon/{name}', name: 'app_pokemon_show', methods: ['GET'])]
    public function show(string $name, Request $request, PokeApiClient $client, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $request->attributes->get('client_id');
        $favorites = $favoriteRepo->findNamesByClientId($clientId);

        try {
            $details = $client->getPokemonDetails($name);
        } catch (PokeApiException $e) {
            throw new NotFoundHttpException('Unable to load Pokemon data.');
        }

        if ($details === null) {
            throw new NotFoundHttpException("Pokemon '{$name}' not found.");
        }

        return $this->render('pokemon/show.html.twig', [
            'pokemon' => $details,
            'isFavorite' => in_array($name, $favorites),
            'favorites' => $favorites,
        ]);
    }
}
