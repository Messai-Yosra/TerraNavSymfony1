<?php

namespace App\Controller\voyages;

use App\Repository\Voyage\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjouterVoyage extends AbstractController
{
    #[Route('/AjouterVoyage', name: 'app_ajouter_voyage')]
    public function AjouterVoyage(OffreRepository $offreRepository): Response
    {
        // Récupérer toutes les offres depuis le repository
        $offres = $offreRepository-> findOffresByAgence(1);

        // Passer les offres au template
        return $this->render('voyages/AjouterVoyage.html.twig', [
            'offres' => $offres,
        ]);
    }
}