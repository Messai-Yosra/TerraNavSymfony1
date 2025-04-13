<?php

namespace App\Controller\voyages;

use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoyageAdminController extends AbstractController
{
    #[Route('/adminvoyages', name: 'admin_voyages')]
    public function index(VoyageRepository $voyageRepository, OffreRepository $offreRepository): Response
    {
        // Récupérer tous les voyages avec leurs offres associées
        $voyages = $voyageRepository->findAllWithOffres();

        // Récupérer toutes les offres actives
        $offres = $offreRepository->findAllActiveOffres();

        return $this->render('voyages/voyageAdmin.html.twig', [
            'voyages' => $voyages,
            'offres' => $offres,
        ]);
    }
}
