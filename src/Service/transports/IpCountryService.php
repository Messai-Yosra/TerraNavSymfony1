<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class IpCountryService
{
    private $httpClient;
    private $logger;
    private $cache;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function getCountryCodeFromIp(string $ip): ?string
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'TN'; // Default to Tunisia for local development
        }

        $cacheKey = 'ip_country_' . md5($ip);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($ip) {
            $item->expiresAfter(3600); // Cache for 1 hour
            try {
                $response = $this->httpClient->request('GET', 'http://ip-api.com/json/' . $ip, ['timeout' => 5]);
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
        });
    }

    public function getPhoneCodeFromCountryCode(string $countryCode): ?string
    {
        $cacheKey = 'phone_code_' . $countryCode;
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($countryCode) {
            $item->expiresAfter(86400); // Cache for 1 day
            try {
                $response = $this->httpClient->request('GET', 'https://restcountries.com/v3.1/alpha/' . $countryCode, ['timeout' => 5]);
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
        });
    }
}