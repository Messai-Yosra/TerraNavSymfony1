<?php

namespace App\Controller\voyages;

use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoyageAdminController extends AbstractController
{
    #[Route('/adminvoyages', name: 'admin_voyages')]
    public function index(
        VoyageRepository $voyageRepository,
        OffreRepository $offreRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $voyages = $voyageRepository->findAllWithOffres();
        $offres = $offreRepository->findAllActiveOffres();

        // Pagination des voyages
        $pagination = $paginator->paginate(
            $voyages,
            $request->query->getInt('page', 1),
            8  // Nombre d'éléments par page
        );

        $stats = [
            'destinations' => $voyageRepository->getDestinationStats(),
            'monthly_voyages' => $voyageRepository->getMonthlyVoyageCounts(),
            'reductions' => $offreRepository->getReductionDistribution(),
            'offer_status' => $offreRepository->getOfferStatusStats(),
            'durationData' => $voyageRepository->getTripDurationStats(),
            'heatmapData' => $voyageRepository->getReservationHeatmapData()
        ];

        return $this->render('voyages/voyageAdmin.html.twig', [
            'voyages' => $pagination,
            'offres' => $offres,
            'stats' => $stats
        ]);
    }
}