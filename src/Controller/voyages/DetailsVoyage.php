<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DetailsVoyage extends AbstractController
{

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