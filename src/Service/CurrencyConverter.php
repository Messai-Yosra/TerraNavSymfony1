<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyConverter
{
    private $httpClient;
    private $apiKey;
    private $baseUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = '8f2980dda40d3baaa2c736d7db2c89cb';
        $this->baseUrl = 'http://data.fixer.io/api/latest';
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl, [
                'query' => [
                    'access_key' => $this->apiKey,
                    'symbols' => implode(',', [$fromCurrency, $toCurrency]),
                ],
            ]);

            $data = $response->toArray();

            if (!$data['success']) {
                throw new \Exception($data['error']['info'] ?? 'Erreur API');
            }

            $rates = $data['rates'];
            $fromRate = $rates[$fromCurrency];
            $toRate = $rates[$toCurrency];

            return $amount * ($toRate / $fromRate);
        } catch (\Exception $e) {
            // En production, vous devriez logger cette erreur
            return null;
        }
    }
}