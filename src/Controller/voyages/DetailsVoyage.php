<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use App\Service\CurrencyConverter;
use App\Service\OpenAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DetailsVoyage extends AbstractController
{
    #[Route('/voyage/{id}', name: 'app_voyage_show')]
    public function show(Voyage $voyage, VoyageRepository $voyageRepository): Response
    {
        $similarVoyages = $voyageRepository->findSimilarVoyages($voyage);

        return $this->render('voyages/DetailsVoyage.html.twig', [
            'voyage' => $voyage,
            'similarVoyages' => $similarVoyages,
        ]);
    }

    #[Route('/offres/Details/{id}', name: 'app_offre_details')]
    public function OffreDetails(Offre $offre, OffreRepository $offreRepository, VoyageRepository $voyageRepository): Response
    {
        return $this->render('voyages/DetailsOffre.html.twig', [
            'offre' => $offre,
            'voyagesAssocies' => $voyageRepository->findVoyagesByOffre($offre),
            'meilleuresOffres' => $offreRepository->findMeilleuresOffres(6, $offre)
        ]);
    }
    #[Route('/agence/{id}', name: 'app_agence_profile')]
    public function agenceProfile(Utilisateur $agence, VoyageRepository $voyageRepository, OffreRepository $offreRepository): Response
    {
        // Vérifier que l'utilisateur est bien une agence


        // Récupérer 3 voyages de l'agence
        $voyages = $voyageRepository->findBy(
            ['id_user' => $agence],
            ['dateDepart' => 'DESC'],
            3
        );

        // Récupérer 3 offres de l'agence
        $offres = $offreRepository->findBy(
            ['id_user' => $agence],
            ['dateFin' => 'ASC'],
            3
        );

        return $this->render('voyages/profileAgence.html.twig', [
            'agence' => $agence,
            'voyages' => $voyages,
            'offres' => $offres,
        ]);
    }

    #[Route('/convert', name: 'app_convert_currency', methods: ['POST'])]
    public function convertCurrency(Request $request, CurrencyConverter $converter): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $convertedAmount = $converter->convert(
                (float) $data['amount'],
                $data['from'],
                $data['to']
            );

            return $this->json([
                'success' => true,
                'convertedAmount' => $convertedAmount
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    #[Route('/get-activities/{destination}', name: 'app_get_activities', methods: ['GET'])]
    public function getActivities(string $destination, OpenAiService $openAiService): JsonResponse
    {
        try {
            $activities = $openAiService->generateActivities($destination);

            return $this->json([
                'success' => true,
                'activities' => $activities
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}