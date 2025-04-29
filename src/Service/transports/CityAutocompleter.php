<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CityAutocompleter
{
    private $httpClient;
    private $logger;
    private $cache;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->cache = new FilesystemAdapter();
    }

    public function getCitySuggestions(string $query, int $limit = 4): array
    {
        if (strlen(trim($query)) < 3) {
            $this->logger->warning('Query too short: {query}', ['query' => $query]);
            return [];
        }

        $query = strtolower(trim($query));
        $cacheKey = 'city_suggestions_' . md5($query);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $this->logger->info('Returning cached city suggestions for query: {query}', ['query' => $query]);
            return array_slice($cacheItem->get(), 0, 4); // Ensure cached results are limited to 4
        }

        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => $limit, // Request only 4 results from Nominatim
                    'featureType' => 'city',
                    'accept-language' => 'fr',
                    'bounded' => 0,
                    'addressdetails' => 1,
                ],
                'headers' => [
                    'User-Agent' => 'TravelAgencySymfony/1.0 (your-email@domain.com)', // Replace with your email
                ],
            ]);

            $data = $response->toArray();
            $this->logger->debug('Nominatim raw response: {response}', ['response' => json_encode($data)]);

            if (empty($data)) {
                $this->logger->warning('No city suggestions found for query: {query}', ['query' => $query]);
                return [];
            }

            $cities = array_filter(array_map(function ($item) {
                $placeTypes = ['city', 'town', 'village', 'locality'];
                $isValidPlace = false;
                foreach ($placeTypes as $type) {
                    if (isset($item['address'][$type])) {
                        $isValidPlace = true;
                        break;
                    }
                }
                if (!$isValidPlace) {
                    return null;
                }

                $name = $item['address']['city'] ?? $item['address']['town'] ?? $item['address']['village'] ?? $item['address']['locality'];
                $country = $item['address']['country'] ?? '';

                if (empty($name) || empty($country)) {
                    return null;
                }

                return [
                    'name' => $name,
                    'country' => $country,
                    'coordinates' => [(float)$item['lon'], (float)$item['lat']],
                    'display_name' => $item['display_name'],
                    'importance' => $item['importance'] ?? 0,
                ];
            }, $data));

            $cities = array_values($cities); // Reindex array after filtering
            usort($cities, function ($a, $b) {
                return $b['importance'] <=> $a['importance']; // Sort by importance
            });

            $cities = array_slice($cities, 0, 4); // Limit to top 4 results

            $cacheItem->set($cities);
            $cacheItem->expiresAfter(604800); // Cache for 7 days
            $this->cache->save($cacheItem);

            $this->logger->info('Fetched {count} city suggestions for query: {query}', [
                'count' => count($cities),
                'query' => $query,
            ]);

            return $cities;
        } catch (\Exception $e) {
            $this->logger->error('Nominatim API error: {message}', ['message' => $e->getMessage()]);
            return [];
        }
    }
}