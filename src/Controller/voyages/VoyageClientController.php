<?php
// src/Controller/VoyageClientController.php

namespace App\Controller\voyages;

use App\Entity\Reservation;
use App\Entity\Voyage;
use App\Repository\Reservation\PanierRepository;
use App\Repository\Reservation\ReservationRepository;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use App\Service\AmadeusService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;

final class VoyageClientController extends AbstractController
{
    #[Route('/VoyagesClient', name: 'app_voyages')]
    public function index(
        Request $request,
        VoyageRepository $voyageRepository,
        PaginatorInterface $paginator
    ): Response {
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

        // Récupération des voyages avec filtres
        $query = $voyageRepository->createQueryBuilderWithFilters($criteria);

        // Pagination
        $pagination = $paginator->paginate(
            $query, // Query à paginer
            $request->query->getInt('page', 1), // Numéro de page
            6 // Nombre d'éléments par page
        );

        return $this->render('voyages/voyageClient.html.twig', [
            'pagination' => $pagination,
            'filterParams' => $criteria,
            'searchTerm' => $criteria['search']
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
        ValidatorInterface $validator,
        Security $security
    ): Response {
        if ($request->isMethod('POST')) {
            $isAjax = $request->isXmlHttpRequest();
            $nbPlaces = $request->request->getInt('nbPlaces', 1);

            // Get the currently logged-in user
            $user = $security->getUser();

            if (!$user) {

                return $this->redirectToRoute('app_login');
            }

            $userId = $user->getId();
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
    #[Route('/voyage/weather/{destination}', name: 'app_voyage_weather')]
    public function getWeather(string $destination, WeatherService $weatherService): JsonResponse
    {
        try {
            $weatherData = $weatherService->getWeatherForDestination($destination);

            if (!$weatherData) {
                return $this->json([
                    'error' => 'weather_data_unavailable',
                    'message' => 'Données météo non disponibles pour cette destination'
                ], 404);
            }

            return $this->json($weatherData);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'api_error',
                'message' => 'Erreur technique lors de la récupération des données météo',
                'details' => $e->getMessage() // En dev seulement
            ], 500);
        }
    }
    // src/Controller/VoyageClientController.php

    #[Route('/search-amadeus', name: 'app_search_amadeus', methods: ['GET'])]
    public function searchAmadeus(
        Request $request,
        AmadeusService $amadeusService
    ): JsonResponse {
        $searchTerm = trim($request->query->get('search', ''));

        if (empty($searchTerm)) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez saisir une destination'
            ], 400);
        }

        try {
            // 1. D'abord on cherche les aéroports correspondant à la destination
            $airports = $amadeusService->searchAirports($searchTerm);

            // 2. Si on trouve des aéroports, on cherche des vols pour chacun
            $allResults = [];
            foreach ($airports as $airport) {
                $results = $amadeusService->searchFlights([
                    'originLocationCode' => 'TUN', // Départ de Tunis
                    'destinationLocationCode' => $airport['iataCode'],
                    'departureDate' => date('Y-m-d', strtotime('+1 week')),
                    'adults' => 1,
                    'max' => 3 // Limite par aéroport
                ]);

                if (!empty($results['data'])) {
                    $allResults = array_merge($allResults, $results['data']);
                }
            }

            return $this->json([
                'success' => true,
                'results' => $allResults
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche : ' . $e->getMessage()
            ], 500);
        }
    }





}