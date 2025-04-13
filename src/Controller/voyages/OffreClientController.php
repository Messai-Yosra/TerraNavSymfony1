<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreClientController extends AbstractController
{
    #[Route('/offres', name: 'app_offres')]
    public function index(Request $request, OffreRepository $offreRepository): Response
    {
        // Nettoyage des paramètres
        $criteria = [
            'search' => $request->query->get('search'),
            'minReduction' => $request->query->get('minReduction'),
            'dateDebut' => $request->query->get('dateDebut'),
            'dateFin' => $request->query->get('dateFin'),
            'sort' => $request->query->get('sort')
        ];

        // Validation des dates
        if ($criteria['dateDebut'] && !\DateTime::createFromFormat('Y-m-d', $criteria['dateDebut'])) {
            unset($criteria['dateDebut']);
        }
        if ($criteria['dateFin'] && !\DateTime::createFromFormat('Y-m-d', $criteria['dateFin'])) {
            unset($criteria['dateFin']);
        }

        $offres = $offreRepository->findFilteredOffres($criteria);

        return $this->render('voyages/OffreClient.html.twig', [
            'offres' => $offres,
            'searchTerm' => $criteria['search'],
            'filterParams' => $criteria
        ]);
    }

    #[Route('/offres/suggestions', name: 'app_offres_suggestions')]
    public function suggestions(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $offreRepository->findTitlesStartingWith($query);

        return $this->json($suggestions);
    }

    #[Route('/offres/Details/{id}', name: 'app_offre_details')]
    public function OffreDetails(Offre $offre, OffreRepository $offreRepository, VoyageRepository $voyageRepository): Response
    {
        return $this->render('voyages/DetailsOffre.html.twig', [
            'offre' => $offre,
            'voyagesAssocies' => $voyageRepository->findVoyagesByOffre($offre),
            'meilleuresOffres' => $offreRepository->findMeilleuresOffres(6, $offre)
        ]);
    }
}