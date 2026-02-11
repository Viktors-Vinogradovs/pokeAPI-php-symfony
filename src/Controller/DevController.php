<?php

namespace App\Controller;

use App\Exception\PokeApiException;
use App\Service\PokeApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DevController extends AbstractController
{
    #[Route('/dev/poke-test', name: 'app_dev_poke_test')]
    public function pokeTest(PokeApiClient $pokeApiClient): Response
    {
        $startTime = microtime(true);

        try {
            // Get all types (but only display first 10)
            $allTypes = $pokeApiClient->getTypes();
            $types = array_slice($allTypes, 0, 10);

            // Get Pikachu details as sample
            $pikachuDetails = $pokeApiClient->getPokemonDetails('pikachu');

            // Get first 5 pokemon for the first type (as sample)
            $sampleType = $types[0] ?? null;
            $samplePokemonList = [];
            if ($sampleType) {
                $fullList = $pokeApiClient->getPokemonListByType($sampleType);
                $samplePokemonList = array_slice($fullList, 0, 5);
            }

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            return $this->render('dev/poke-test.html.twig', [
                'types' => $types,
                'totalTypes' => count($allTypes),
                'sampleType' => $sampleType,
                'samplePokemonList' => $samplePokemonList,
                'pikachuDetails' => $pikachuDetails,
                'responseTime' => $responseTime,
                'error' => null,
            ]);
        } catch (PokeApiException $e) {
            return $this->render('dev/poke-test.html.twig', [
                'types' => [],
                'totalTypes' => 0,
                'sampleType' => null,
                'samplePokemonList' => [],
                'pikachuDetails' => null,
                'responseTime' => 0,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
