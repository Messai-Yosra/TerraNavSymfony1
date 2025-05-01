<?php

namespace App\Twig;

use App\Service\hebergements\GeoLocationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PriceExtension extends AbstractExtension
{
    private $geoLocationService;

    public function __construct(GeoLocationService $geoLocationService)
    {
        $this->geoLocationService = $geoLocationService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('local_price', [$this, 'convertToLocalPrice']),
            new TwigFilter('format_phone', [$this, 'formatPhoneNumber']),
        ];
    }

    public function convertToLocalPrice(float $price): array
    {
        $countryInfo = $this->geoLocationService->getCurrentCountryInfo();
        return [
            'amount' => round($price * $countryInfo['exchange_rate'], 2),
            'currency' => $countryInfo['currency'],
            'original' => $price,
            'country' => $countryInfo['country']
        ];
    }

    public function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $countryInfo = $this->geoLocationService->getCurrentCountryInfo();
        // Enlever tout préfixe existant et reformater avec le préfixe local
        $cleanPhone = preg_replace('/^\+\d+/', '', $phone);
        return $countryInfo['phone_prefix'] . $cleanPhone;
    }
} 