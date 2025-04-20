<?php

namespace App\Service\transports;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CityAutocompleter
{
    private $httpClient;
    private $mapboxToken;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->mapboxToken = $params->get('mapbox_access_token');
    }

    public function getCitySuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                'https://api.mapbox.com/geocoding/v5/mapbox.places/'.urlencode($query).'.json',
                [
                    'query' => [
                        'access_token' => $this->mapboxToken,
                        'types' => 'place,locality', // Types de résultats (villes/localités)
                        'language' => 'fr', // Langue des résultats
                        'limit' => 5 // Nombre de suggestions
                    ]
                ]
            );

            $data = $response->toArray();

            return array_map(function($feature) {
                return [
                    'name' => $feature['place_name'],
                    'coordinates' => $feature['center'] // [longitude, latitude]
                ];
            }, $data['features']);

        } catch (\Exception $e) {
            // En environnement dev, vous pouvez logger l'erreur
            return [];
        }
    }
}