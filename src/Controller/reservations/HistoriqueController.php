<?php

namespace App\Controller\reservations;

use App\Repository\Reservation\ReservationRepository;
use App\Repository\Reservation\PanierRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
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
            'unique_destinations' => count($destinations),
            'username' => $user->getUsername()
        ]);
    }

    #[Route('/reservation/{id}/qr-code', name: 'app_reservation_qr_code')]
    public function generateQrCode(int $id, Request $request, ReservationRepository $reservationRepository, Security $security): Response
    {
        $user = $security->getUser();
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($this->generateQrCodeData($reservation,$user))
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->margin(10)
            ->build();

        // Generate appropriate filename
        $prefix = match(strtolower($reservation->gettype_service())) {
            'voyage' => 'VOY',
            'chambre' => 'HEB',
            'transport' => 'TRP',
            default => 'RES'
        };
        $filename = $prefix . '-' . $reservation->getId() . '-qrcode.png';

        // Check if download was requested
        $isDownload = $request->query->getBoolean('download');

        $response = new Response($qrCode->getString());

        if ($isDownload) {
            // Force download with proper headers
            $response->headers->set('Content-Type', 'image/png');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        } else {
            // Regular inline display
            $response->headers->set('Content-Type', $qrCode->getMimeType());
            $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');
        }

        return $response;
    }

    private function generateQrCodeData($reservation, $user): string
    {
        return json_encode([
            'reservation' => [
                'id' => $reservation->getId(),
                'type' => $reservation->gettype_service(),
                'status' => $reservation->getEtat(),
                'reference' => $this->generateReference($reservation),
                'price' => $reservation->getPrix(),
                'date' => $reservation->getdate_reservation()->format('Y-m-d H:i'),
                'places' => $reservation->getnb_places()
            ],
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'Email' => $user->getEmail(),
            ]
        ]);
    }

    private function generateReference($reservation): string
    {
        $type = strtolower($reservation->gettype_service());
        $prefix = match($type) {
            'voyage' => 'VOY',
            'chambre' => 'HEB',
            'transport' => 'TRP',
            default => 'RES'
        };

        return $prefix . '-' . $reservation->getId();
    }
}