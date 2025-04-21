<?php
// src/Service/AmadeusService.php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AmadeusService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private string $apiSecret;
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(string $amadeusApiKey, string $amadeusApiSecret)
    {
        $this->client = HttpClient::create();
        $this->apiKey = $amadeusApiKey;
        $this->apiSecret = $amadeusApiSecret;
    }

    /**
     * Authentifie auprès de l'API Amadeus et obtient un token d'accès
     */
    private function authenticate(): void
    {
        try {
            $response = $this->client->request('POST', 'https://test.api.amadeus.com/v1/security/oauth2/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                ],
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->tokenExpiresAt = time() + $data['expires_in'];
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Erreur d\'authentification avec Amadeus: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie et renouvelle le token si nécessaire
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->accessToken || time() >= $this->tokenExpiresAt - 30) {
            $this->authenticate();
        }
    }

    /**
     * Recherche des vols basés sur des critères
     */
    // src/Service/AmadeusService.php

    public function searchFlights(array $criteria): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->client->request('GET', 'https://test.api.amadeus.com/v2/shopping/flight-offers', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->accessToken,
                ],
                'query' => array_filter($criteria), // Supprime les valeurs null
            ]);

            $data = $response->toArray();

            if (isset($data['errors'])) {
                throw new \RuntimeException('API Error: '.json_encode($data['errors']));
            }

            return $data;
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Erreur réseau: '.$e->getMessage());
        }
    }

    /**
     * Recherche des hôtels basés sur des critères
     */
    public function searchHotels(array $criteria): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->client->request('GET', 'https://test.api.amadeus.com/v3/shopping/hotel-offers', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ],
                'query' => $criteria,
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Erreur lors de la recherche d\'hôtels: ' . $e->getMessage());
        }
    }

    public function searchAirports(string $keyword): array
    {
        $this->ensureAuthenticated();

        try {
            $response = $this->client->request('GET', 'https://test.api.amadeus.com/v1/reference-data/locations', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->accessToken,
                ],
                'query' => [
                    'subType' => 'AIRPORT',
                    'keyword' => $keyword,
                    'view' => 'LIGHT'
                ]
            ]);

            $data = $response->toArray();

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur recherche aéroports: '.$e->getMessage());
        }
    }
}