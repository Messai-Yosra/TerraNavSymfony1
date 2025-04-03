<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreClientController extends AbstractController
{
    #[Route('/offres', name: 'app_offres')]
    public function index(Request $request, OffreRepository $offreRepository): Response
    {
        $searchTerm = $request->query->get('search');
        $minReduction = $request->query->get('minReduction');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $sortType = $request->query->get('sort');

        $offres = $offreRepository->findFilteredOffres([
            'search' => $searchTerm,
            'minReduction' => $minReduction,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'sort' => $sortType
        ]);

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