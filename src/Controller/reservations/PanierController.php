<?php

namespace App\Controller\reservations;

use App\Entity\Reservation;
use App\Entity\Panier;
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
        $userId = 1; // Static user ID - you might want to get this from security context
        $panier = $panierRepository->findByUser($userId);

        if (!$panier) {
            $panier = $panierRepository->createPanierForUser($userId);
        }

        $panierId = $panier->getId();
        $total = $panier->getPrixTotal() ?? 0;

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
        ReservationRepository $reservationRepository,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_panier');
        }

        try {
            $reservation = $reservationRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Reservation not found');
            }

            $panier = $reservation->getid_panier();
            $entityManager->remove($reservation);
            $entityManager->flush();

            // Update panier total after deletion
            $panierRepository->updateTotalPrice($panier->getId());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Reservation deleted successfully']);
            }

            $this->addFlash('success', 'Reservation deleted successfully');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Error deleting reservation: '.$e->getMessage()], 500);
            }
            $this->addFlash('error', 'Error deleting reservation: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/reservation/{id}/update', name: 'app_reservation_update', methods: ['POST'])]
    public function update(
        Request $request,
        int $id,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        PanierRepository $panierRepository,
        LoggerInterface $logger
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('update-reservation', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Reservation not found'], 404);
        }

        try {
            $type = $reservation->gettype_service();

            // Handle type-specific fields
            switch ($type) {
                case 'Voyage':
                    $nbPlaces = (int)$request->request->get('nb_places');
                    if ($nbPlaces <= 0) {
                        return new JsonResponse(['success' => false, 'message' => 'Number of places must be positive'], 400);
                    }

                    // Calculate new price based on price per place
                    $voyage = $reservation->getId_voyage();
                    if ($voyage) {
                        $pricePerPlace = $reservation->getPrix() / $reservation->getNb_places();
                        $newPrice = $pricePerPlace * $nbPlaces;
                        $reservation->setPrix($newPrice);
                    }

                    $reservation->setNb_places($nbPlaces);
                    break;

                case 'Chambre':
                    $nbJours = (int)$request->request->get('nbJoursHebergement');
                    if ($nbJours <= 0) {
                        return new JsonResponse(['success' => false, 'message' => 'Number of days must be positive'], 400);
                    }

                    // Calculate new price based on price per day
                    $chambre = $reservation->getId_Chambre();
                    if ($chambre) {
                        $pricePerDay = $reservation->getPrix() / $reservation->getnbJoursHebergement();
                        $newPrice = $pricePerDay * $nbJours;
                        $reservation->setPrix($newPrice);
                    }

                    $reservation->setnbJoursHebergement($nbJours);

                    $dateValue = $request->request->get('date_reservation');
                    if (!$dateValue) {
                        return new JsonResponse(['success' => false, 'message' => 'Date is required'], 400);
                    }
                    $reservation->setdate_reservation(new \DateTime($dateValue));
                    break;

                case 'Transport':
                    $dateValue = $request->request->get('date_reservation');
                    if (!$dateValue) {
                        return new JsonResponse(['success' => false, 'message' => 'Date is required'], 400);
                    }
                    $dateReservation = new \DateTime($dateValue);
                    $reservation->setdate_reservation($dateReservation);
                    $reservation->setdateAffectation($dateReservation);
                    break;
            }

            $entityManager->flush();

            // Update panier total
            $panierRepository->updateTotalPrice($reservation->getid_panier()->getId());

            return new JsonResponse([
                'success' => true,
                'message' => 'Reservation updated successfully',
                'reservation' => [
                    'id' => $reservation->getId(),
                    'type' => $reservation->gettype_service(),
                    'price' => $reservation->getPrix(),
                    'date' => $reservation->getdate_reservation()->format('Y-m-d'),
                    'status' => $reservation->getEtat(),
                    'nb_places' => $reservation->getNb_places(),
                    'nb_jours' => $reservation->getnbJoursHebergement()
                ]
            ]);

        } catch (\Exception $e) {
            $logger->error('Update error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating reservation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/panier/confirm/{panierId}', name: 'app_panier_confirm', methods: ['POST'])]
    public function confirmPanier(
        int $panierId,
        Request $request,
        ReservationRepository $reservationRepository,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('confirm-panier', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        try {
            // Find all PENDING reservations for this panier
            $reservations = $reservationRepository->findBy([
                'id_panier' => $panierId,
                'Etat' => 'PENDING'
            ]);

            if (empty($reservations)) {
                return new JsonResponse(['success' => false, 'message' => 'No pending reservations found'], 404);
            }

            // Update each reservation status
            $count = 0;
            foreach ($reservations as $reservation) {
                $reservation->setEtat('CONFIRMED');
                $count++;
            }
            $entityManager->flush();

            // Validate panier (sets date_validation and prix_total = 0)
            $panier = $panierRepository->find($panierId);
            $panier->setDateValidation(new \DateTime());
            $panier->setPrixTotal(0);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Payment confirmed and reservations updated',
                'count' => $count,
                'newTotal' => 0
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error confirming payment: ' . $e->getMessage()
            ], 500);
        }
    }
}