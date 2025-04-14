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
        $voyages = $voyageRepository->findAllWithOffres();
        $offres = $offreRepository->findAllActiveOffres();

        $stats = [
            'destinations' => $voyageRepository->getDestinationStats(),
            'monthly_voyages' => $voyageRepository->getMonthlyVoyageCounts(),
            'reductions' => $offreRepository->getReductionDistribution(),
            'offer_status' => $offreRepository->getOfferStatusStats() ,
            'durationData' => $voyageRepository->getTripDurationStats(),
            'heatmapData' => $voyageRepository->getReservationHeatmapData()// Utilisez la nouvelle méthode
        ];

        return $this->render('voyages/voyageAdmin.html.twig', [
            'voyages' => $voyages,
            'offres' => $offres,
            'stats' => $stats
        ]);
    }
}
