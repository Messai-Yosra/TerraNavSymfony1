<?php
// src/Controller/VoyageClientController.php

namespace App\Controller\voyages;

use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class VoyageClientController extends AbstractController
{
    #[Route('/VoyagesClient', name: 'app_voyages')]
    public function index(Request $request, VoyageRepository $voyageRepository): Response
    {
        $filterParams = $request->query->all();

        // Nettoyage des paramètres
        $criteria = [
            'search' => $filterParams['search'] ?? null,
            'minPrice' => isset($filterParams['minPrice']) && is_numeric($filterParams['minPrice']) ?
                (float)$filterParams['minPrice'] : null,
            'maxPrice' => isset($filterParams['maxPrice']) && is_numeric($filterParams['maxPrice']) ?
                (float)$filterParams['maxPrice'] : null,
            'minPlaces' => isset($filterParams['minPlaces']) && is_numeric($filterParams['minPlaces']) ?
                (int)$filterParams['minPlaces'] : null,
            'type' => $filterParams['type'] ?? null,
            'onSale' => $request->query->has('onSale'),
            'sort' => $filterParams['sort'] ?? null
        ];

        // Validation des prix
        if ($criteria['minPrice'] !== null && $criteria['maxPrice'] !== null
            && $criteria['minPrice'] > $criteria['maxPrice']) {
            $criteria['maxPrice'] = $criteria['minPrice'];
        }

        // Récupération des voyages
        $voyages = $voyageRepository->findByFilters($criteria);

        return $this->render('voyages/voyageClient.html.twig', [
            'voyages' => $voyages,
            'filterParams' => $criteria,
            'searchTerm' => $criteria['search']
        ]);
    }

    #[Route('/voyage/{id}', name: 'app_voyage_show')]
    public function show(Voyage $voyage, VoyageRepository $voyageRepository): Response
    {
        $similarVoyages = $voyageRepository->findSimilarVoyages($voyage);

        return $this->render('voyages/DetailsVoyage.html.twig', [
            'voyage' => $voyage,
            'similarVoyages' => $similarVoyages,
        ]);
    }
    #[Route('/Reservervoyage/{id}', name: 'app_voyage_reserver')]
    public function Reserver(Voyage $voyage): Response
    {
        return $this->render('voyages/ReserverVoyage.html.twig', [
            'voyage' => $voyage
        ]);
    }
    #[Route('/voyages/suggestions', name: 'app_voyages_suggestions')]
    public function suggestions(Request $request, VoyageRepository $voyageRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $voyageRepository->findTitlesStartingWith($query);

        return $this->json($suggestions);
    }



}