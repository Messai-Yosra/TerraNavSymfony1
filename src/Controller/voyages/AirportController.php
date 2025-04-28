<?php
// src/Controller/Api/AirportController.php

namespace App\Controller\voyages;

use App\Service\AviationStackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/airports')]
class AirportController extends AbstractController
{
    #[Route('/search', name: 'api_airport_search', methods: ['GET'])]
    public function search(Request $request, AviationStackService $aviationStack): JsonResponse
    {
        $searchTerm = $request->query->get('query', '');
         // À supprimer



        if (empty($searchTerm)) {
            return $this->json([
                'success' => false,
                'error' => 'Paramètre "query" manquant'
            ]);
        }

        try {
            $airports = $aviationStack->searchAirports($searchTerm);

            return $this->json([
                'success' => true,
                'data' => array_map(function($airport) {
                    return [
                        'iata_code' => $airport['iata_code'],
                        'airport_name' => $airport['airport_name'],
                        'city' => $airport['city_name'],
                        'country' => $airport['country_name'],
                        'latitude' => $airport['latitude'],
                        'longitude' => $airport['longitude']
                    ];
                }, $airports)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/details/{iata}', name: 'api_airport_details', methods: ['GET'])]
    public function details(string $iata, AviationStackService $aviationStack): JsonResponse
    {
        $airport = $aviationStack->getAirportByIata($iata);

        if (!$airport) {
            return $this->json(['error' => 'Airport not found'], 404);
        }

        return $this->json($airport);
    }
}