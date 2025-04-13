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
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PanierController extends AbstractController
{
    #[Route('/PanierClient', name: 'app_panier')]
    public function index(ReservationRepository $reservationRepository, PanierRepository $panierRepository): Response
    {
        $userId = 1; // ID utilisateur statique - vous pourriez vouloir l'obtenir du contexte de sécurité
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

        return $this->render('reservations/panierClient.html.twig', [
            'reservations' => $groupedReservations,
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
                return new JsonResponse(['success' => false, 'message' => 'Jeton CSRF invalide'], 403);
            }
            $this->addFlash('error', 'Jeton CSRF invalide');
            return $this->redirectToRoute('app_panier');
        }

        try {
            $reservation = $reservationRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation non trouvée');
            }

            $panier = $reservation->getid_panier();
            $entityManager->remove($reservation);
            $entityManager->flush();

            // Mettre à jour le total du panier après suppression
            $panierRepository->updateTotalPrice($panier->getId());

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Réservation supprimée avec succès']);
            }

            $this->addFlash('success', 'Réservation supprimée avec succès');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression de la réservation: '.$e->getMessage()], 500);
            }
            $this->addFlash('error', 'Erreur lors de la suppression de la réservation: '.$e->getMessage());
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
        LoggerInterface $logger,
        ValidatorInterface $validator
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('update-reservation', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Jeton CSRF invalide'], 403);
        }

        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Réservation non trouvée'], 404);
        }

        try {
            $type = $reservation->gettype_service();
            $newPrice = $reservation->getPrix();

            switch ($type) {
                case 'Voyage':
                    $nbPlaces = (int)$request->request->get('nb_places');
                    $oldPlaces = $reservation->getNb_places();

                    // Validate places using Symfony asserts
                    $reservation->setNb_places($nbPlaces);
                    $errors = $validator->validate($reservation);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getMessage();
                        }
                        return new JsonResponse(['success' => false, 'message' => implode(', ', $errorMessages)], 400);
                    }

                    // Check available places
                    $voyage = $reservation->getId_voyage();
                    if ($voyage) {
                        $placeDifference = $nbPlaces - $oldPlaces;
                        if ($placeDifference > $voyage->getNbPlacesD()) {
                            return new JsonResponse([
                                'success' => false,
                                'message' => 'Pas assez de places disponibles. Seulement ' . $voyage->getNbPlacesD() . ' places restantes.'
                            ], 400);
                        }
                        $voyage->setNbPlacesD($voyage->getNbPlacesD() - $placeDifference);
                    }

                    // Calculate new price
                    $pricePerPlace = $reservation->getPrix() / $oldPlaces;
                    $newPrice = $pricePerPlace * $nbPlaces;
                    $reservation->setPrix($newPrice);
                    break;

                case 'Chambre':
                    $nbJours = (int)$request->request->get('nbJoursHebergement');
                    $oldDays = $reservation->getnbJoursHebergement();
                    $dateValue = $request->request->get('date_reservation');

                    // First validate required fields
                    if (!$dateValue) {
                        return new JsonResponse(['success' => false, 'message' => 'La date est requise'], 400);
                    }

                    // Set all properties that need validation at once
                    $reservation->setnbJoursHebergement($nbJours);
                    $reservation->setdate_reservation(new \DateTime($dateValue));

                    // Validate the entity including both nbJours and date
                    $errors = $validator->validate($reservation);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getMessage();
                        }
                        return new JsonResponse(['success' => false, 'message' => implode(', ', $errorMessages)], 400);
                    }

                    // Calculate new price only after validation passes
                    $pricePerDay = $reservation->getPrix() / $oldDays;
                    $newPrice = $pricePerDay * $nbJours;
                    $reservation->setPrix($newPrice);

                    break;

                case 'Transport':

                    // Validate date using Symfony asserts
                    $dateValue = $request->request->get('date_reservation');
                    if (!$dateValue) {
                        return new JsonResponse(['success' => false, 'message' => 'La date est requise'], 400);
                    }
                    $reservation->setdate_reservation(new \DateTime($dateValue));
                    $errors = $validator->validate($reservation);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getMessage();
                        }
                        return new JsonResponse(['success' => false, 'message' => implode(', ', $errorMessages)], 400);
                    }
                    break;
            }

            $entityManager->flush();
            $panierRepository->updateTotalPrice($reservation->getid_panier()->getId());

            return new JsonResponse([
                'success' => true,
                'message' => 'Réservation mise à jour avec succès',
                'reservation' => [
                    'id' => $reservation->getId(),
                    'type' => $type,
                    'price' => $newPrice,
                    'date' => $reservation->getdate_reservation() ? $reservation->getdate_reservation()->format('Y-m-d') : null,
                    'status' => $reservation->getEtat(),
                    'nb_places' => $type === 'Voyage' ? $nbPlaces : null,
                    'nb_jours' => $type === 'Chambre' ? $nbJours : null
                ]
            ]);

        } catch (\Exception $e) {
            $logger->error('Erreur de mise à jour', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la réservation: ' . $e->getMessage()
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
            return new JsonResponse(['success' => false, 'message' => 'Jeton CSRF invalide'], 403);
        }

        try {
            // Trouver toutes les réservations PENDING pour ce panier
            $reservations = $reservationRepository->findBy([
                'id_panier' => $panierId,
                'Etat' => 'PENDING'
            ]);

            if (empty($reservations)) {
                return new JsonResponse(['success' => false, 'message' => 'Aucune réservation en attente trouvée'], 404);
            }

            // Mettre à jour le statut de chaque réservation
            $count = 0;
            foreach ($reservations as $reservation) {
                $reservation->setEtat('CONFIRMED');
                $count++;
            }
            $entityManager->flush();

            // Valider le panier (définit date_validation et prix_total = 0)
            $panier = $panierRepository->find($panierId);
            $panier->setDateValidation(new \DateTime());
            $panier->setPrixTotal(0);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Paiement confirmé et réservations mises à jour',
                'count' => $count,
                'newTotal' => 0
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la confirmation du paiement: ' . $e->getMessage()
            ], 500);
        }
    }
}