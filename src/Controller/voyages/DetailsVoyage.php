<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
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
    #[Route('/agence/{id}', name: 'app_agence_profile')]
    public function agenceProfile(Utilisateur $agence, VoyageRepository $voyageRepository, OffreRepository $offreRepository): Response
    {
        // Vérifier que l'utilisateur est bien une agence


        // Récupérer 3 voyages de l'agence
        $voyages = $voyageRepository->findBy(
            ['id_user' => $agence],
            ['dateDepart' => 'DESC'],
            3
        );

        // Récupérer 3 offres de l'agence
        $offres = $offreRepository->findBy(
            ['id_user' => $agence],
            ['dateFin' => 'ASC'],
            3
        );

        return $this->render('voyages/profileAgence.html.twig', [
            'agence' => $agence,
            'voyages' => $voyages,
            'offres' => $offres,
        ]);
    }

}