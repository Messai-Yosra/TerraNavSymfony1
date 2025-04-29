<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpCountryService
{
    private $httpClient;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getCountryCodeFromIp(string $ip): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'http://ip-api.com/json/' . $ip);
            $data = $response->toArray();

            if ($data['status'] === 'success') {
                return $data['countryCode'] ?? null;
            }

            $this->logger->warning('Échec de la récupération du code pays pour l\'IP: ' . $ip);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du code pays: ' . $e->getMessage());
            return null;
        }
    }

    public function getPhoneCodeFromCountryCode(string $countryCode): ?string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://restcountries.com/v3.1/alpha/' . $countryCode);
            $data = $response->toArray();

            if (!empty($data)) {
                $countryData = $data[0];
                if (isset($countryData['idd']['root']) && !empty($countryData['idd']['suffixes'])) {
                    return $countryData['idd']['root'] . $countryData['idd']['suffixes'][0];
                }
            }

            $this->logger->warning('Échec de la récupération de l\'indicatif téléphonique pour le code pays: ' . $countryCode);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'indicatif téléphonique: ' . $e->getMessage());
            return null;
        }
    }
}