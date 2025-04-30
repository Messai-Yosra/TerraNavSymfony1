<?php

namespace App\Service\interactions;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfanityFilter
{
    private $httpClient;
    private $apiUrl = 'https://www.purgomalum.com/service/plain?text=';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function filter(string $text): string
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiUrl . urlencode($text)
            );

            if ($response->getStatusCode() === 200) {
                return $response->getContent();
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        // Return original text if API call fails
        return $text;
    }
}