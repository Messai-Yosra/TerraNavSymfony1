<?php

namespace App\Controller\reservations;

use App\Repository\Reservation\ReservationRepository;
use App\Repository\Reservation\PanierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HistoriqueController extends AbstractController
{
    #[Route('/HistoriqueClient', name: 'app_historique')]
    public function index(ReservationRepository $reservationRepository, PanierRepository $panierRepository, Security $security): Response
    {
        // Get the currently logged-in user
        $user = $security->getUser();

        if (!$user) {
            // Handle case where user is not logged in (redirect to login or show error)
            return $this->redirectToRoute('app_login');
        }

        $userId = $user->getId(); // Static user ID - replace with actual user when auth is implemented

        // Get all validated paniers for this user
        $paniers = $panierRepository->createQueryBuilder('p')
            ->where('p.id_user = :userId')
            ->andWhere('p.date_validation IS NOT NULL')
            ->setParameter('userId', $userId)
            ->orderBy('p.date_validation', 'DESC')
            ->getQuery()
            ->getResult();

        // Get all reservations (both CONFIRMED and PENDING) from these paniers
        $reservations = [];
        $totalSpent = 0;
        $destinations = [];

        foreach ($paniers as $panier) {
            $panierReservations = $reservationRepository->findBy(
                ['id_panier' => $panier],
                ['date_reservation' => 'DESC']
            );

            foreach ($panierReservations as $reservation) {
                $type = strtolower($reservation->gettype_service());
                if (!isset($reservations[$type])) {
                    $reservations[$type] = [];
                }
                $reservations[$type][] = $reservation;

                // Calculate total spent only for confirmed reservations
                if ($reservation->getEtat() === 'CONFIRMED') {
                    $totalSpent += $reservation->getprix();
                }

                // Collect unique destinations for voyages
                if ($type === 'voyage' && $reservation->getid_voyage() && $reservation->getid_voyage()->getdestination()) {
                    $destination = $reservation->getid_voyage()->getdestination();
                    if (!in_array($destination, $destinations)) {
                        $destinations[] = $destination;
                    }
                }
            }
        }

        return $this->render('reservations/historiqueClient.html.twig', [
            'reservations' => [
                'voyage' => $reservations['voyage'] ?? [],
                'chambre' => $reservations['chambre'] ?? [],
                'transport' => $reservations['transport'] ?? []
            ],
            'total_reservations' => array_reduce($reservations, function($carry, $items) {
                return $carry + count($items);
            }, 0),
            'total_spent' => $totalSpent,
            'unique_destinations' => count($destinations)
        ]);
    }
}