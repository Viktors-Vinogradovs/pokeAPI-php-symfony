<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PokemonController extends AbstractController
{
    private const PER_PAGE = 20;

    #[Route('/pokemon', name: 'app_pokemon_list', methods: ['GET'])]
    public function list(Request $request, PokeApiClient $client): Response
    {
        try {
            // Extract query parameters
            $searchTerm = trim(strtolower($request->query->get('q', '')));
            $selectedTypes = array_filter($request->query->all('types') ?? []);
            $page = max(1, (int) $request->query->get('page', 1));

            // Get all types for filter UI
            $allTypes = $client->getTypes();

            // Filter out invalid type names
            $selectedTypes = array_values(array_intersect($selectedTypes, $allTypes));

            // Determine base pokemon list
            if (empty($selectedTypes) && empty($searchTerm)) {
                // CASE 1: No filters - use API pagination
                $offset = ($page - 1) * self::PER_PAGE;
                $pageData = $client->getPokemonPage(self::PER_PAGE, $offset);

                $totalCount = $pageData['count'];
                $pokemonNames = $pageData['results'];
            } else {
                // CASE 2: Filters present - build filtered list in memory
                if (!empty($selectedTypes)) {
                    // Fetch and intersect type lists
                    $typeLists = [];
                    foreach ($selectedTypes as $type) {
                        $typeLists[] = $client->getPokemonListByType($type);
                    }
                    $baseList = count($typeLists) > 1
                        ? array_intersect(...$typeLists)
                        : $typeLists[0];
                } else {
                    // No types selected but search present - get large cached index
                    $pageData = $client->getPokemonPage(2000, 0);
                    $baseList = $pageData['results'];
                }

                // Apply search filter
                if (!empty($searchTerm)) {
                    $baseList = array_filter($baseList, function ($name) use ($searchTerm) {
                        return str_contains(strtolower($name), $searchTerm);
                    });
                }

                // Paginate filtered list
                $baseList = array_values($baseList); // Re-index array
                $totalCount = count($baseList);
                $totalPages = (int) ceil($totalCount / self::PER_PAGE);
                $page = min($page, max(1, $totalPages)); // Clamp page
                $offset = ($page - 1) * self::PER_PAGE;
                $pokemonNames = array_slice($baseList, $offset, self::PER_PAGE);
            }

            // Calculate pagination
            $totalPages = (int) ceil($totalCount / self::PER_PAGE);
            $page = min($page, max(1, $totalPages)); // Ensure valid page

            // Fetch details only for current page
            $pokemonDetails = [];
            foreach ($pokemonNames as $name) {
                $details = $client->getPokemonDetails($name);
                if ($details !== null) {
                    $pokemonDetails[] = $details;
                }
            }

            return $this->render('pokemon/list.html.twig', [
                'pokemonList' => $pokemonDetails,
                'searchTerm' => $searchTerm,
                'selectedTypes' => $selectedTypes,
                'allTypes' => $allTypes,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'perPage' => self::PER_PAGE,
                'hasResults' => !empty($pokemonDetails),
            ]);
        } catch (PokeApiException $e) {
            return $this->render('pokemon/list.html.twig', [
                'error' => $e->getMessage(),
                'pokemonList' => [],
                'searchTerm' => '',
                'selectedTypes' => [],
                'allTypes' => [],
                'page' => 1,
                'totalPages' => 0,
                'totalCount' => 0,
                'perPage' => self::PER_PAGE,
                'hasResults' => false,
            ]);
        }
    }
}
