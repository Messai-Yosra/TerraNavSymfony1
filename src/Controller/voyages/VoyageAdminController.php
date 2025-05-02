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
    ): Response
    {
        $activeTab = $request->query->get('active_tab', 'voyages');
        // Pagination pour les voyages
        $voyagesQuery = $voyageRepository->createQueryBuilder('v')
            ->leftJoin('v.id_offre', 'o')
            ->addSelect('o')
            ->getQuery();

        $voyages = $paginator->paginate(
            $voyagesQuery,
            $request->query->getInt('page_voyages', 1),
            10, // Nombre d'éléments par page
            [
                'pageParameterName' => 'page_voyages'
            ]
        );

        // Pagination pour les offres
        $offresQuery = $offreRepository->createQueryBuilder('o')
            ->getQuery();

        $offres = $paginator->paginate(
            $offresQuery,
            $request->query->getInt('page_offres', 1),
            10, // Nombre d'éléments par page
            [
                'pageParameterName' => 'page_offres'
            ]
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
            'voyages' => $voyages,
            'offres' => $offres,
            'stats' => $stats,
            'active_tab' => $activeTab // Passer l'onglet actif au template
        ]);
    }
}