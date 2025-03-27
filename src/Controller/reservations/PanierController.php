<?php

namespace App\Controller\reservations;

use App\Repository\Reservation\PanierRepository;
use App\Repository\Reservation\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class PanierController extends AbstractController
{
    #[Route('/PanierClient', name: 'app_panier')]
    public function index(ReservationRepository $reservationRepository, PanierRepository $panierRepository): Response
    {
        $userId = 1; // Static panier ID
        $panierId = $panierRepository->findByUser($userId)->getId(); // Static panier ID

        // Get all reservations for this panier
        $allReservations = $reservationRepository->findByPanier($panierId);

        // Filter by type and status
        $groupedReservations = [
            'voyage' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Voyage' && $r->getEtat() === 'PENDING'),
            'transport' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Transport' && $r->getEtat() === 'PENDING'),
            'chambre' => array_filter($allReservations, fn($r) =>
                $r->gettype_service() === 'Chambre' && $r->getEtat() === 'PENDING')
        ];

        // Static images remain the same
        $staticImages = [
            'voyage' => ['https://images.unsplash.com/photo-1431274172761-fca41d930114','https://images.unsplash.com/photo-1431274172761-fca41d930114'],
            'transport' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70',
            'chambre' => ['https://images.unsplash.com/photo-1566073771259-6a8506099945','https://images.unsplash.com/photo-1566073771259-6a8506099945']
        ];

        // Calculate total
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
    ): Response {
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
}