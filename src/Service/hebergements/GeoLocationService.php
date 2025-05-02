<?php

namespace App\Service\hebergements;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class GeoLocationService
{
    private $client;
    private $requestStack;
    private $cache;
    private const IPSTACK_API_KEY = '313e12655ab15742bfec7517ce9b2ba3';
    private const EXCHANGERATE_API_KEY = '4bf4e310f387e720f06198df';

    public function __construct(
        HttpClientInterface $client,
        RequestStack $requestStack
    ) {
        $this->client = $client;
        $this->requestStack = $requestStack;
        $this->cache = new FilesystemAdapter();
    }

    public function getCurrentCountryInfo(): array
    {
        // Vérifier d'abord si une devise a été sélectionnée manuellement
        $session = $this->requestStack->getSession();
        $selectedCurrency = $session->get('currency', 'auto');
        
        if ($selectedCurrency !== 'auto') {
            return [
                'country' => 'Custom',
                'countryCode' => 'XX',
                'currency' => $selectedCurrency,
                'exchange_rate' => $this->getExchangeRateFromApi($selectedCurrency),
                'phone_prefix' => '+XXX'
            ];
        }

        $ip = $this->requestStack->getCurrentRequest()->getClientIp();
        
        // Pour le développement local
        if ($ip === '127.0.0.1') {
            $ip = '41.231.123.80'; // IP tunisienne pour les tests
        }

        // Clé de cache basée sur l'IP
        $cacheKey = 'geo_' . str_replace('.', '_', $ip);
        $cachedInfo = $this->cache->getItem($cacheKey);

        if ($cachedInfo->isHit()) {
            return $cachedInfo->get();
        }

        try {
            // Utilisation de l'API ipstack pour la géolocalisation
            $response = $this->client->request(
                'GET',
                "http://api.ipstack.com/{$ip}?access_key=" . self::IPSTACK_API_KEY
            );
            
            $data = $response->toArray();
            
            if (isset($data['country_code'])) {
                $countryCode = $data['country_code'];
                $currency = $this->getCurrencyForCountry($countryCode);
                
                $countryInfo = [
                    'country' => $data['country_name'],
                    'countryCode' => $countryCode,
                    'currency' => $currency,
                    'exchange_rate' => $this->getExchangeRateFromApi($currency),
                    'phone_prefix' => $this->getPhonePrefix($countryCode)
                ];

                // Cache pour 1 heure
                $cachedInfo->set($countryInfo);
                $cachedInfo->expiresAfter(3600);
                $this->cache->save($cachedInfo);

                return $countryInfo;
            }
        } catch (\Exception $e) {
            // Valeurs par défaut pour la Tunisie
            return $this->getDefaultCountryInfo();
        }

        // Valeurs par défaut si quelque chose ne va pas
        return $this->getDefaultCountryInfo();
    }

    private function getCurrencyForCountry(string $countryCode): string
    {
        $currencies = [
            'TN' => 'TND',
            'FR' => 'EUR',
            'US' => 'USD',
            'GB' => 'GBP',
            'DE' => 'EUR',
            'IT' => 'EUR',
            'ES' => 'EUR'
        ];
        return $currencies[$countryCode] ?? 'EUR';
    }

    private function getExchangeRateFromApi(string $currency): float
    {
        if ($currency === 'EUR') {
            return 1.0;
        }

        $cacheKey = 'exchange_rate_' . $currency;
        $cachedRate = $this->cache->getItem($cacheKey);

        if ($cachedRate->isHit()) {
            return $cachedRate->get();
        }

        try {
            // Utilisation de l'API exchangerate pour obtenir les taux de change
            $response = $this->client->request(
                'GET',
                "https://v6.exchangerate-api.com/v6/" . self::EXCHANGERATE_API_KEY . "/latest/EUR"
            );
            
            $data = $response->toArray();
            
            if (isset($data['conversion_rates'][$currency])) {
                $rate = $data['conversion_rates'][$currency];
                
                // Cache pour 24 heures
                $cachedRate->set($rate);
                $cachedRate->expiresAfter(86400); // 24 heures
                $this->cache->save($cachedRate);
                
                return $rate;
            }
        } catch (\Exception $e) {
            return $this->getDefaultExchangeRate($currency);
        }

        return $this->getDefaultExchangeRate($currency);
    }

    private function getDefaultExchangeRate(string $currency): float
    {
        $rates = [
            'TND' => 3.4,
            'USD' => 1.1,
            'GBP' => 0.86,
            'EUR' => 1.0
        ];
        return $rates[$currency] ?? 1.0;
    }

    private function getPhonePrefix(string $countryCode): string
    {
        $prefixes = [
            'TN' => '+216',
            'FR' => '+33',
            'US' => '+1',
            'GB' => '+44',
            'DE' => '+49',
            'IT' => '+39',
            'ES' => '+34'
        ];
        return $prefixes[$countryCode] ?? '+216';
    }

    private function getDefaultCountryInfo(): array
    {
        return [
            'country' => 'Tunisia',
            'countryCode' => 'TN',
            'currency' => 'TND',
            'exchange_rate' => 3.4,
            'phone_prefix' => '+216'
        ];
    }
} 