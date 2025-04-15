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
        // Récupérer les paramètres de filtrage
        $searchTerm = $request->query->get('search');
        $minReduction = $request->query->get('minReduction');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $sortType = $request->query->get('sort');

        // Construire les critères de filtrage
        $criteria = [];
        if ($searchTerm !== null) {
            $criteria['search'] = $searchTerm;
        }
        if ($minReduction !== null) {
            $criteria['minReduction'] = $minReduction;
        }
        if ($dateDebut !== null) {
            $criteria['dateDebut'] = $dateDebut;
        }
        if ($dateFin !== null) {
            $criteria['dateFin'] = $dateFin;
        }
        if ($sortType !== null) {
            $criteria['sort'] = $sortType;
        }

        // Récupérer les offres filtrées
        $offres = $offreRepository->findFilteredOffres($criteria);

        return $this->render('voyages/OffreClient.html.twig', [
            'offres' => $offres,
            'searchTerm' => $searchTerm,
            'filterParams' => [
                'minReduction' => $minReduction,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'sort' => $sortType
            ]
        ]);
    }

    #[Route('/offres/suggestions', name: 'app_offres_suggestions')]
    public function suggestions(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $offreRepository->findTitlesStartingWith($query);

        // Retourne directement un tableau simple de strings
        return $this->json(array_column($suggestions, 'titre'));
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