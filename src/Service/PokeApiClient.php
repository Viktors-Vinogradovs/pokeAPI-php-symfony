<?php

namespace App\Service;

use App\Exception\PokeApiException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PokeApiClient
{
    private const CACHE_TTL = 86400; // 1 day

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $baseUrl = 'https://pokeapi.co/api/v2'
    ) {
    }

    /**
     * Get list of all Pokemon types
     *
     * @return array<string> Array of type names
     * @throws PokeApiException
     */
    public function getTypes(): array
    {
        try {
            return $this->cache->get('poke.types', function (ItemInterface $item) {
                $item->expiresAfter(self::CACHE_TTL);

                $response = $this->request('/type');
                return $this->normalizeTypes($response);
            });
        } catch (InvalidArgumentException $e) {
            throw new PokeApiException('Cache error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get list of Pokemon names for a specific type
     *
     * @param string $type Type name (e.g., 'fire', 'water')
     * @return array<string> Array of Pokemon names
     * @throws PokeApiException
     */
    public function getPokemonListByType(string $type): array
    {
        try {
            $cacheKey = sprintf('poke.type.%s', strtolower($type));

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($type) {
                $item->expiresAfter(self::CACHE_TTL);

                $response = $this->request("/type/{$type}");
                return $this->normalizePokemonList($response);
            });
        } catch (InvalidArgumentException $e) {
            throw new PokeApiException('Cache error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get detailed information about a Pokemon
     *
     * @param string $name Pokemon name
     * @return array|null Normalized Pokemon data or null if not found
     * @throws PokeApiException
     */
    public function getPokemonDetails(string $name): ?array
    {
        try {
            $cacheKey = sprintf('poke.pokemon.%s', strtolower($name));

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($name) {
                $item->expiresAfter(self::CACHE_TTL);

                try {
                    $response = $this->request("/pokemon/{$name}");
                    return $this->normalizePokemonDetails($response);
                } catch (PokeApiException $e) {
                    // If 404, don't cache and return null
                    if (str_contains($e->getMessage(), '404')) {
                        $item->expiresAfter(0); // Don't cache 404s
                        return null;
                    }
                    throw $e;
                }
            });
        } catch (InvalidArgumentException $e) {
            throw new PokeApiException('Cache error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Make HTTP request to PokeAPI
     *
     * @param string $endpoint API endpoint (e.g., '/type', '/pokemon/pikachu')
     * @return array Decoded JSON response
     * @throws PokeApiException
     */
    private function request(string $endpoint): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $endpoint, [
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 404) {
                throw new PokeApiException("Resource not found: {$endpoint} (404)");
            }

            if ($statusCode >= 500) {
                throw new PokeApiException("PokeAPI server error (HTTP {$statusCode})");
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PokeApiException('Invalid JSON response: ' . json_last_error_msg());
            }

            return $data;
        } catch (TransportExceptionInterface $e) {
            throw new PokeApiException('Network error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Normalize types list response
     *
     * @param array $response Raw API response
     * @return array<string> Array of type names
     */
    private function normalizeTypes(array $response): array
    {
        if (!isset($response['results']) || !is_array($response['results'])) {
            return [];
        }

        return array_map(
            fn(array $item) => $item['name'],
            $response['results']
        );
    }

    /**
     * Normalize Pokemon list response
     *
     * @param array $response Raw API response from /type/{type}
     * @return array<string> Array of Pokemon names
     */
    private function normalizePokemonList(array $response): array
    {
        if (!isset($response['pokemon']) || !is_array($response['pokemon'])) {
            return [];
        }

        return array_map(
            fn(array $item) => $item['pokemon']['name'],
            $response['pokemon']
        );
    }

    /**
     * Normalize Pokemon details response
     *
     * @param array $response Raw API response from /pokemon/{name}
     * @return array Normalized Pokemon data
     */
    private function normalizePokemonDetails(array $response): array
    {
        // Extract sprite (prefer front_default, fallback to official-artwork)
        $sprite = $response['sprites']['front_default'] ?? null;
        if (!$sprite && isset($response['sprites']['other']['official-artwork']['front_default'])) {
            $sprite = $response['sprites']['other']['official-artwork']['front_default'];
        }

        // Extract types
        $types = array_map(
            fn(array $typeData) => $typeData['type']['name'],
            $response['types'] ?? []
        );

        // Extract stats as associative array [statName => baseStat]
        $stats = [];
        foreach ($response['stats'] ?? [] as $statData) {
            $statName = $statData['stat']['name'];
            $stats[$statName] = $statData['base_stat'];
        }

        // Extract abilities
        $abilities = array_map(
            fn(array $abilityData) => $abilityData['ability']['name'],
            $response['abilities'] ?? []
        );

        return [
            'id' => $response['id'] ?? null,
            'name' => $response['name'] ?? 'unknown',
            'sprite' => $sprite,
            'types' => $types,
            'stats' => $stats,
            'abilities' => $abilities,
        ];
    }
}
