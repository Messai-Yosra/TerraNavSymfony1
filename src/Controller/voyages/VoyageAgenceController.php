<?php
namespace App\Controller\voyages;

use App\Entity\Voyage;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

final class VoyageAgenceController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/VoyagesAgence', name: 'app_voyages_agence')]
    public function index(Request $request, VoyageRepository $voyageRepository, UtilisateurRepository $userRepository): Response
    {
        // Récupérer les paramètres de filtrage
        $searchTerm = $request->query->get('search');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $minPlaces = $request->query->get('minPlaces');
        $type = $request->query->get('type');
        $onSale = $request->query->get('onSale');
        $sortType = $request->query->get('sort');

        // Récupérer l'utilisateur avec ID=1
        $user = $userRepository->find(1);

        // Construire les critères de filtrage
        $criteria = [];

        // Si l'utilisateur a un nom d'agence, filtrer par ce nom
        if ($user && $user->getNomagence()) {
            $criteria['nomAgence'] = $user->getNomagence();
        }

        // Ajouter les autres critères de filtrage
        if ($minPrice !== null) $criteria['minPrice'] = $minPrice;
        if ($maxPrice !== null) $criteria['maxPrice'] = $maxPrice;
        if ($minPlaces !== null) $criteria['minPlaces'] = $minPlaces;
        if ($type !== null && $type !== 'all') $criteria['type'] = $type;
        if ($onSale !== null) $criteria['onSale'] = true;
        if ($searchTerm !== null) $criteria['search'] = $searchTerm;
        if ($sortType !== null) $criteria['sort'] = $sortType;

        // Récupérer les voyages filtrés
        $voyages = $voyageRepository->findByFiltersAgence($criteria);

        $expiredVoyages = $voyageRepository->findExpiredVoyages();
        $expiredVoyagesData = array_map(function($voyage) {
            return [
                'id' => $voyage->getId(),
                'titre' => $voyage->getTitre(),
                'dateRetour' => $voyage->getDateRetour()->format('Y-m-d H:i:s')
            ];
        }, $expiredVoyages);

        return $this->render('voyages/voyageAgence.html.twig', [
            'voyages' => $voyages,
            'expiredVoyages' => $expiredVoyages,
            'expiredVoyagesData' => $expiredVoyagesData, // Données pour le JS
            'filterParams' => [
                'search' => $searchTerm,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'minPlaces' => $minPlaces,
                'type' => $type,
                'onSale' => $onSale,
                'sort' => $sortType
            ]
        ]);
    }
    #[Route('/voyageAgence/{id}', name: 'app_voyage_show_agence')]
    public function show(Voyage $voyage, VoyageRepository $voyageRepository): Response
    {
        $similarVoyages = $voyageRepository->findSimilarVoyages($voyage);

        return $this->render('voyages/DetailsVoyageAgence.html.twig', [
            'voyage' => $voyage,
            'similarVoyages' => $similarVoyages,
        ]);
    }



}