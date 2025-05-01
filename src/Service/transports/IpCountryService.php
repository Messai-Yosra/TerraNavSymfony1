<?php

namespace App\Service\transports;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpCountryService
{
    private $httpClient;
    private $logger;
    private $defaultPhoneCode = '+216'; // Code par défaut pour la Tunisie

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function getCountryCodeFromIp(string $ip): ?string
    {
        // Si c'est une IP locale ou de développement, retourner directement TN
        if ($this->isLocalIp($ip)) {
            return 'TN';
        }
        
        try {
            // Utilisation de HTTP pour la version gratuite de ip-api.com
            $response = $this->httpClient->request('GET', 'http://ip-api.com/json/' . $ip, [
                'timeout' => 2.0 // 2 secondes max
            ]);
            
            $data = $response->toArray();

            if (isset($data['status']) && $data['status'] === 'success') {
                return $data['countryCode'] ?? 'TN';
            }

            $this->logger->warning('Échec de la récupération du code pays pour l\'IP: ' . $ip);
            return 'TN'; // Valeur par défaut en cas d'échec
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du code pays: ' . $e->getMessage());
            return 'TN'; // Valeur par défaut en cas d'erreur
        }
    }

    public function getPhoneCodeFromCountryCode(string $countryCode): string
    {
        // Valeurs hardcodées pour les codes téléphoniques courants
        $phoneCodes = [
            'TN' => '+216', // Tunisie
            'FR' => '+33',  // France
            'US' => '+1',   // États-Unis
            'CA' => '+1',   // Canada
            'GB' => '+44',  // Royaume-Uni
            'DE' => '+49',  // Allemagne
            'IT' => '+39',  // Italie
            'ES' => '+34',  // Espagne
            'MA' => '+212', // Maroc
            'DZ' => '+213', // Algérie
            'EG' => '+20',  // Égypte
        ];
        
        // Si nous avons déjà le code dans notre liste, l'utiliser directement
        if (isset($phoneCodes[$countryCode])) {
            return $phoneCodes[$countryCode];
        }
        
        try {
            $response = $this->httpClient->request('GET', 'https://restcountries.com/v3.1/alpha/' . $countryCode, [
                'timeout' => 2.0 // 2 secondes max
            ]);
            
            $data = $response->toArray();

            if (!empty($data)) {
                $countryData = $data[0];
                if (isset($countryData['idd']['root']) && !empty($countryData['idd']['suffixes'])) {
                    return $countryData['idd']['root'] . $countryData['idd']['suffixes'][0];
                }
            }

            $this->logger->warning('Échec de la récupération de l\'indicatif téléphonique pour le code pays: ' . $countryCode);
            return $this->defaultPhoneCode;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'indicatif téléphonique: ' . $e->getMessage());
            return $this->defaultPhoneCode;
        }
    }
    
    /**
     * Vérifie si une adresse IP est locale
     */
    private function isLocalIp(string $ip): bool
    {
        return (
            $ip === '127.0.0.1' ||
            $ip === '::1' ||
            substr($ip, 0, 8) === '192.168.' ||
            substr($ip, 0, 4) === '10.' ||
            substr($ip, 0, 7) === '172.16.' ||
            $ip === 'localhost'
        );
    }
}