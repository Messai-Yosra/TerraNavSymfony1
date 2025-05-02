<?php
// src/Service/AviationStackService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AviationStackService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $aviationStackApiKey)
    {
        $this->client = $client;
        $this->apiKey = $aviationStackApiKey;
    }

    /**
     * Recherche des aéroports par terme (ville ou nom d'aéroport)
     */
    public function searchAirports(string $searchTerm): array
    {
        try {
            $response = $this->client->request('GET', 'http://api.aviationstack.com/v1/airports', [
                'query' => [
                    'access_key' => $this->apiKey,
                    'search' => $searchTerm,
                    'limit' => 10
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['data'])) {
                return ['results' => []];
            }

            // Format pour Select2
            $results = [];
            foreach ($data['data'] as $airport) {
                $results[] = [
                    'iata_code' => $airport['iata_code'] ?? '',
                    'airport_name' => $airport['airport_name'] ?? 'Unknown',
                    'city_name' => $airport['city'] ?? 'Unknown',
                    'country_name' => $airport['country'] ?? 'Unknown',
                    'latitude' => $airport['latitude'] ?? 0,
                    'longitude' => $airport['longitude'] ?? 0
                ];
            }

            return ['results' => $results];
        } catch (\Exception $e) {
            return ['results' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Récupère les informations d'un aéroport spécifique par son code IATA
     */
    public function getAirportByIata(string $iataCode): ?array
    {
        try {
            $response = $this->client->request('GET', 'http://api.aviationstack.com/v1/airports', [
                'query' => [
                    'access_key' => $this->apiKey,
                    'iata_code' => $iataCode
                ]
            ]);

            $data = $response->toArray();

            if (isset($data['data'][0])) {
                return $data['data'][0];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}