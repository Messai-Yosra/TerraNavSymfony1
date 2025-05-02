<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreAgence extends AbstractController
{
    #[Route('/offresAgence', name: 'app_offres_agence')]
    public function index(Request $request, OffreRepository $offreRepository, UtilisateurRepository $userRepository): Response
    {
        // Utiliser l'utilisateur connecté au lieu d'un ID statique
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }

        $searchTerm = $request->query->get('search');
        $minReduction = $request->query->get('minReduction');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $sortType = $request->query->get('sort');

        $criteria = [];

        // Si l'utilisateur a un nom d'agence, filtrer par ce nom
        if ($user && $user->getNomagence()) {
            $criteria['nomAgence'] = $user->getNomagence();
        }
        if ($searchTerm !== null) $criteria['search'] = $searchTerm;
        if ($minReduction !== null) $criteria['minReduction'] = $minReduction;
        if ($dateDebut !== null) $criteria['dateDebut'] = $dateDebut;
        if ($dateFin !== null) $criteria['dateFin'] = $dateFin;
        if ($sortType !== null) $criteria['sort'] = $sortType;


        $offres = $offreRepository->findFilteredOffresAgence($criteria);

        return $this->render('voyages/OffreAgence.html.twig', [
            'offres' => $offres,
            'filterParams' => [
                'searchTerm' => $searchTerm,
                'minReduction' => $minReduction,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'sort' => $sortType
            ]
        ]);
    }

    #[Route('/offreAgence/{id}', name: 'app_offre_show_agence')]
    public function show(Offre $offre, VoyageRepository $voyageRepository): Response
    {
        // Calculer les jours restants avant expiration
        $now = new \DateTime();
        $joursRestants = $offre->getDateFin()->diff($now)->days;

        // Récupérer les voyages associés
        $voyagesAssocies = $voyageRepository->findBy(['id_offre' => $offre->getId()]);

        return $this->render('voyages/DetailsOffreAgence.html.twig', [
            'offre' => $offre,
            'joursRestants' => $joursRestants,
            'voyagesAssocies' => $voyagesAssocies,
        ]);
    }



}