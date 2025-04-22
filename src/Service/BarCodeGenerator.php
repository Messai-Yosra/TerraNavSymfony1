<?php
// src/Service/BarcodeGenerator.php
namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BarCodeGenerator
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function generateOfferBarcodeUrl(int $offerId): string
    {
// Génère l'URL absolue vers la page de détail de l'offre
        $offerUrl = $this->router->generate('app_offre_details', ['id' => $offerId], UrlGeneratorInterface::ABSOLUTE_URL);

// Retourne l'URL de l'API QuickChart pour générer le QR code
        return sprintf('https://quickchart.io/qr?text=%s&size=200', urlencode($offerUrl));
    }
}