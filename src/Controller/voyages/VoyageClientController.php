<?php
// src/Controller/VoyageClientController.php

namespace App\Controller\voyages;

use App\Entity\Reservation;
use App\Entity\Voyage;
use App\Repository\Reservation\PanierRepository;
use App\Repository\Reservation\ReservationRepository;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class VoyageClientController extends AbstractController
{
    #[Route('/VoyagesClient', name: 'app_voyages')]
    public function index(Request $request, VoyageRepository $voyageRepository): Response
    {
        $filterParams = $request->query->all();

        // Nettoyage des paramètres
        $criteria = [
            'search' => $filterParams['search'] ?? null,
            'minPrice' => isset($filterParams['minPrice']) && is_numeric($filterParams['minPrice']) ?
                (float)$filterParams['minPrice'] : null,
            'maxPrice' => isset($filterParams['maxPrice']) && is_numeric($filterParams['maxPrice']) ?
                (float)$filterParams['maxPrice'] : null,
            'minPlaces' => isset($filterParams['minPlaces']) && is_numeric($filterParams['minPlaces']) ?
                (int)$filterParams['minPlaces'] : null,
            'type' => $filterParams['type'] ?? null,
            'onSale' => $request->query->has('onSale'),
            'sort' => $filterParams['sort'] ?? null
        ];

        // Validation des prix
        if ($criteria['minPrice'] !== null && $criteria['maxPrice'] !== null
            && $criteria['minPrice'] > $criteria['maxPrice']) {
            $criteria['maxPrice'] = $criteria['minPrice'];
        }

        // Récupération des voyages
        $voyages = $voyageRepository->findByFilters($criteria);

        return $this->render('voyages/voyageClient.html.twig', [
            'voyages' => $voyages,
            'filterParams' => $criteria,
            'searchTerm' => $criteria['search']
        ]);
    }

    #[Route('/voyage/{id}', name: 'app_voyage_show')]
    public function show(Voyage $voyage, VoyageRepository $voyageRepository): Response
    {
        $similarVoyages = $voyageRepository->findSimilarVoyages($voyage);

        return $this->render('voyages/DetailsVoyage.html.twig', [
            'voyage' => $voyage,
            'similarVoyages' => $similarVoyages,
        ]);
    }

    #[Route('/voyages/suggestions', name: 'app_voyages_suggestions')]
    public function suggestions(Request $request, VoyageRepository $voyageRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $voyageRepository->findTitlesStartingWith($query);

        return $this->json($suggestions);
    }

    #[Route('/Reservervoyage/{id}', name: 'app_voyage_reserver', methods: ['GET', 'POST'])]
    public function Reserver(
        Voyage $voyage,
        Request $request,
        PanierRepository $panierRepo,
        ReservationRepository $reservationRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('POST')) {
            $isAjax = $request->isXmlHttpRequest();
            $nbPlaces = $request->request->getInt('nbPlaces', 1);

            // Get or create panier for user ID 1
            $userId = 252;
            $panier = $panierRepo->findByUser($userId) ?? $panierRepo->createPanierForUser($userId);

            $price = $voyage->getId_offre() ?
                ($voyage->getPrix() * (1 - $voyage->getId_offre()->getReduction()/100)) * $nbPlaces :
                $voyage->getPrix() * $nbPlaces;

            $reservation = new Reservation();
            $reservation->setId_voyage($voyage);
            $reservation->setId_panier($panier);
            $reservation->setType_service('Voyage');
            $reservation->setPrix($price);
            $reservation->setDate_reservation($voyage->getDateDepart());
            $reservation->setDateAffectation(new \DateTime());
            $reservation->setEtat('PENDING');
            $reservation->setNb_places($nbPlaces);

            // Validate against entity asserts
            $errors = $validator->validate($reservation);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'success' => false,
                    'message' => implode(', ', $errorMessages)
                ], 400);
            }

            // Check available places after validation
            if ($nbPlaces > $voyage->getNbPlacesD()) {
                return $this->json([
                    'success' => false,
                    'message' => sprintf('Seulement %d places disponibles.', $voyage->getNbPlacesD())
                ], 400);
            }

            try {
                $voyage->setNbPlacesD($voyage->getNbPlacesD() - $nbPlaces);
                $reservationRepo->create($reservation);
                $panierRepo->updateTotalPrice($panier->getId());

                return $this->json([
                    'success' => true,
                    'message' => 'Réservation confirmée et ajoutée à votre panier!',
                    'remainingPlaces' => $voyage->getNbPlacesD()
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la réservation'
                ], 500);
            }
        }

        return $this->render('voyages/ReserverVoyage.html.twig', [
            'voyage' => $voyage
        ]);
    }

}