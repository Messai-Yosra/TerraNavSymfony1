<?php
// src/Service/WeatherService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $weatherApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $weatherApiKey;
    }

    public function getWeatherForDestination(string $destination): ?array
    {
        try {
            $url = 'https://api.weatherstack.com/current?access_key='.$this->apiKey.'&query='.urlencode($destination);
            error_log("Weather API URL: ".$url); // Debug

            $response = $this->httpClient->request(
                'GET',
                'https://api.weatherstack.com/current',
                [
                    'query' => [
                        'access_key' => $this->apiKey,
                        'query' => $destination,
                        'units' => 'm'
                    ],
                    'max_redirects' => 0 // Empêche les redirections
                ]
            );

            $content = $response->getContent(false); // false pour voir les erreurs
            error_log("API Response: ".$content); // Debug

            $data = $response->toArray();

            if (isset($data['current'])) {
                return [
                    'temperature' => $data['current']['temperature'],
                    'weather_descriptions' => $data['current']['weather_descriptions'][0],
                    'weather_icon' => $data['current']['weather_icons'][0],
                    'wind_speed' => $data['current']['wind_speed'],
                    'humidity' => $data['current']['humidity'],
                    'feelslike' => $data['current']['feelslike'],
                ];
            }

            return null;
        } catch (\Exception $e) {
// En production, vous pourriez logger cette erreur
            return null;
        }
    }
}