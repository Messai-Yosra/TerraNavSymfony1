<?php

namespace App\Controller\reservations;

use App\Entity\Reservation;
use App\Repository\Reservation\PanierRepository;
use App\Repository\Reservation\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

final class PanierController extends AbstractController
{
    #[Route('/PanierClient', name: 'app_panier')]
    public function index(ReservationRepository $reservationRepository, PanierRepository $panierRepository): Response
    {
        $userId = 1; // Static panier ID
        $panierId = $panierRepository->findByUser($userId)->getId();

        $allReservations = $reservationRepository->findByPanier($panierId);

        $groupedReservations = [
            'voyage' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Voyage' && $r->getEtat() === 'PENDING'),
            'transport' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Transport' && $r->getEtat() === 'PENDING'),
            'chambre' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Chambre' && $r->getEtat() === 'PENDING')
        ];

        $staticImages = [
            'voyage' => ['https://images.unsplash.com/photo-1431274172761-fca41d930114','https://images.unsplash.com/photo-1431274172761-fca41d930114'],
            'transport' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70',
            'chambre' => ['https://images.unsplash.com/photo-1566073771259-6a8506099945','https://images.unsplash.com/photo-1566073771259-6a8506099945']
        ];

        $total = array_reduce(
            $allReservations,
            fn($sum, $r) => $r->getEtat() === 'PENDING' ? $sum + $r->getPrix() : $sum,
            0
        );

        return $this->render('reservations/panierClient.html.twig', [
            'reservations' => $groupedReservations,
            'static_images' => $staticImages,
            'total' => $total,
            'panierId' => $panierId
        ]);
    }

    #[Route('/reservation/delete/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository
    ): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_panier');
        }

        try {
            $reservationRepository->delete($id);
            $this->addFlash('success', 'La réservation a été supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Échec de la suppression de la réservation');
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/reservation/{id}/update', name: 'app_reservation_update', methods: ['POST'])]
    public function update(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('update-reservation', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Reservation not found'], 404);
        }

        try {
            $type = $reservation->gettype_service();
            $dateValue = $request->request->get('date_reservation');

            if (!$dateValue) {
                return new JsonResponse(['success' => false, 'message' => 'Date is required'], 400);
            }

            try {
                $dateReservation = new \DateTime($dateValue);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid date format'], 400);
            }

            $today = new \DateTime('today');
            if ($dateReservation < $today) {
                return new JsonResponse(['success' => false, 'message' => 'Date must be today or in the future'], 400);
            }

            // Update date_reservation for all types
            $reservation->setdate_reservation($dateReservation);

            // For Transport, sync dateAffectation
            if ($type === 'Transport') {
                $reservation->setdateAffectation($dateReservation);
            }

            // Handle type-specific fields
            switch ($type) {
                case 'Voyage':
                    $nbPlaces = (int)$request->request->get('nb_places');
                    if ($nbPlaces <= 0) {
                        return new JsonResponse(['success' => false, 'message' => 'Number of places must be positive'], 400);
                    }
                    $reservation->setnb_places($nbPlaces);
                    break;

                case 'Chambre':
                    $nbJours = (int)$request->request->get('nbJoursHebergement');
                    if ($nbJours <= 0) {
                        return new JsonResponse(['success' => false, 'message' => 'Number of days must be positive'], 400);
                    }
                    $reservation->setnbJoursHebergement($nbJours);
                    break;
            }

            $entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'Reservation updated successfully']);

        } catch (\Exception $e) {
            $logger->error('Update error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Error updating reservation: ' . $e->getMessage()], 500);
        }
    }
}