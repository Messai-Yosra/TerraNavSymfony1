<?php
// src/Controller/CalendarController.php
namespace App\Controller\reservations;

use App\Repository\Reservation\PanierRepository;
use App\Repository\Reservation\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Reservation;

class CalendarController extends AbstractController
{
    #[Route('/mes-reservations/calendrier', name: 'mes_reservations_calendar')]
    public function showCalendar(): Response
    {
        return $this->render('reservations/calendar.html.twig');
    }

    #[Route('/api/calendar/events', name: 'api_calendar_events', methods: ['GET'])]
    public function getEvents(ReservationRepository $reservationRepository, PanierRepository $panierRepository, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(["message" => "Utilisateur non connecté"], 401);
        }

        $userId = $user->getId();
        $panier = $panierRepository->findByUser($userId);

        if (!$panier) {
            $panier = $panierRepository->createPanierForUser($userId);
        }

        $panierId = $panier->getId();

        $reservations = $reservationRepository->findByPanier($panierId);

        $events = [];
        foreach ($reservations as $reservation) {
            $event = [
                'title' => $this->generateEventTitle($reservation),
                'start' => $reservation->getdate_reservation()->format('Y-m-d'),
                'end' => $reservation->getdate_reservation()->format('Y-m-d'),
                'color' => $this->getEventColor($reservation->gettype_service()),
                'extendedProps' => [
                    'type' => $reservation->gettype_service(),
                    'status' => $reservation->getEtat(),
                    'price' => $reservation->getPrix(),
                    'reference' => $this->generateReference($reservation)
                ]
            ];

            // event = caldendar element data
            if ($reservation->gettype_service() === 'voyage' && $reservation->getid_voyage()) {
                $event['extendedProps']['point_depart'] = $reservation->getid_voyage()->getPointDepart();
                $event['extendedProps']['destination'] = $reservation->getid_voyage()->getDestination();
            }
            elseif ($reservation->gettype_service() === 'chambre' && $reservation->getid_Chambre()) {
                $event['extendedProps']['hebergement'] = $reservation->getid_Chambre()->getId_hebergement()->getNom();
            }
            elseif ($reservation->gettype_service() === 'transport' && $reservation->getid_Transport()) {
                $event['extendedProps']['point_depart'] = $reservation->getid_Transport()->getId_trajet()->getPointDepart();
                $event['extendedProps']['vehicule'] = $reservation->getid_Transport()->getNom();
            }

            $events[] = $event;
        }

        return new JsonResponse($events);
    }

    private function generateEventTitle(Reservation $reservation): string
    {
        $type = strtolower($reservation->gettype_service());
        $prefix = match($type) {
            'voyage' => 'VOY',
            'chambre' => 'HEB',
            'transport' => 'TRP',
            default => 'RES'
        };

        $details = '';
        if ($type === 'voyage' && $reservation->getid_voyage()) {
            $details = $reservation->getid_voyage()->getDestination();
        } elseif ($type === 'chambre' && $reservation->getid_Chambre()) {
            $details = $reservation->getid_Chambre()->getId_hebergement()->getNom();
        } elseif ($type === 'transport' && $reservation->getid_Transport()) {
            $details = $reservation->getid_Transport()->getNom();
        }

        return $prefix.'-'.$reservation->getId().($details ? ' ('.$details.')' : '');
    }

    private function getEventColor(string $type): string
    {
        return match(strtolower($type)) {
            'voyage' => '#3498db', // Blue
            'chambre' => '#2ecc71', // Green
            'transport' => '#e74c3c', // Red
            default => '#9b59b6' // Purple
        };
    }

    private function generateReference(Reservation $reservation): string
    {
        $type = strtolower($reservation->gettype_service());
        $prefix = match($type) {
            'voyage' => 'VOY',
            'chambre' => 'HEB',
            'transport' => 'TRP',
            default => 'RESS'
        };

        return $prefix . '-' . $reservation->getId();
    }
}
