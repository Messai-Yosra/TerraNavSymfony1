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

final class VoyageClientController extends AbstractController
{
    #[Route('/VoyagesClient', name: 'app_voyages')]
    public function index(Request $request, VoyageRepository $voyageRepository): Response
    {
        // Récupérer les paramètres de filtrage
        $searchTerm = $request->query->get('search');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $minPlaces = $request->query->get('minPlaces');
        $type = $request->query->get('type');
        $onSale = $request->query->get('onSale');
        $sortType = $request->query->get('sort');

        // Construire les critères de filtrage
        $criteria = [];
        if ($minPrice !== null) {
            $criteria['minPrice'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $criteria['maxPrice'] = $maxPrice;
        }
        if ($minPlaces !== null) {
            $criteria['minPlaces'] = $minPlaces;
        }
        if ($type !== null && $type !== 'all') {
            $criteria['type'] = $type;
        }
        if ($onSale !== null) {
            $criteria['onSale'] = true;
        }
        if ($searchTerm !== null) {
            $criteria['search'] = $searchTerm;
        }
        if ($sortType !== null) {
            $criteria['sort'] = $sortType;
        }


        // Récupérer les voyages filtrés
        $voyages = $voyageRepository->findByFilters($criteria);

        // Traitement des images multiples
        foreach ($voyages as $voyage) {
            if ($voyage->getPathImages()) {
                $images = explode('***', $voyage->getPathImages());
                $voyage->setImageList(array_map(function($path) {
                    return str_replace('\\', '/', $path);
                }, $images));
            }
        }

        return $this->render('voyages/voyageClient.html.twig', [
            'voyages' => $voyages,
            'filterParams' => [
                'search' => $searchTerm,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'minPlaces' => $minPlaces,
                'type' => $type,
                'onSale' => $onSale,
                'sort' => $sortType
            ]
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
    #[Route('/voyage/{id}', name: 'app_voyage_reserver')]
    public function Reserver(Voyage $voyage): Response
    {
        return $this->render('voyages/DetailsVoyage.html.twig', [
            'voyage' => $voyage
        ]);
    }

}