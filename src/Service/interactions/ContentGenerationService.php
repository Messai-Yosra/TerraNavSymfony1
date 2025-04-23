<?php

namespace App\Service\interactions;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentGenerationService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function generateContent(string $prompt): ?string
    {
        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='; // Replace with the actual API URL
        $apiKey = 'AIzaSyCpqpCJ4zMZKHbEY4frJ36dSJX3gjJhpm8AIzaSyCpqpCJ4zMZKHbEY4frJ36dSJX3gjJhpm8'; // Replace with your API key

        $response = $this->httpClient->request('POST', $apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'prompt' => $prompt,
                'max_tokens' => 100, 
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            return $data['content'] ?? null; 
        }

        return null;
    }
}