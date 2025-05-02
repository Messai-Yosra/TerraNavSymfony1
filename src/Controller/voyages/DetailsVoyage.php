<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use App\Service\CurrencyConverter;
use App\Service\OpenAiService;
use App\Service\OpenRouterService;
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
    public function getActivities(string $destination, OpenRouterService $openRouter): JsonResponse
    {
        try {
            $prompt = "Donne-moi 3 activités populaires à faire à $destination.
Pour chaque activité, fournis un JSON valide avec :
- name: nom court (max 5 mots)
- description: une bonne description (max 40 mots)
- longitude: coordonnée longitude (exemple: 2.294481)
- latitude: coordonnée latitude (exemple: 48.858370)

Format de sortie STRICT :
{
\"activities\": [
    {
        \"name\": \"Tour Eiffel\",
        \"description\": \"Monument emblématique de Paris...(reste de la description)\",
        \"longitude\": 2.294481,
        \"latitude\": 48.858370
    }
]
}";

            $response = $openRouter->askQuestion($prompt);

            // Debug - à enlever en production
            file_put_contents('openrouter_activities.log', $response, FILE_APPEND);

            // Essayez d'extraire le JSON de la réponse
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');

            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Format de réponse inattendu de l\'API');
            }

            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $data = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Réponse JSON invalide: '.json_last_error_msg());
            }

            if (!isset($data['activities'])) {
                throw new \Exception('Structure de réponse inattendue');
            }

            // Validation des coordonnées
            foreach ($data['activities'] as &$activity) {
                if (!isset($activity['longitude']) || !isset($activity['latitude'])) {
                    // Générer des coordonnées aléatoires près du centre-ville si non fournies
                    $activity['longitude'] = mt_rand(-1800000, 1800000) / 100000;
                    $activity['latitude'] = mt_rand(-900000, 900000) / 100000;
                }
            }

            return $this->json([
                'success' => true,
                'activities' => $data['activities'],
                'mapboxToken' => $this->getParameter('mapbox_access_token')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate-description', name: 'app_translate_description', methods: ['POST'])]
    public function translateDescription(Request $request, OpenRouterService $openRouter): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $languages = [
                'en' => ['name' => 'Anglais', 'flag' => 'us.png'],
                'es' => ['name' => 'Espagnol', 'flag' => 'es.png'],
                'de' => ['name' => 'Allemand', 'flag' => 'de.png'],
                'it' => ['name' => 'Italien', 'flag' => 'it.png'],
                'ar' => ['name' => 'Arabe', 'flag' => 'rs.png'],
                'tr' => ['name' => 'Turc', 'flag' => 'tr.png']
            ];

            $targetLang = $data['targetLang'];
            $description = $data['description'];

            if (!array_key_exists($targetLang, $languages)) {
                throw new \Exception('Langue non supportée');
            }

            $prompt = "Traduis ce texte de voyage touristique en {$languages[$targetLang]['name']} de manière naturelle et attrayante:\n\n";
            $prompt .= $description;
            $prompt .= "\n\nConserve le ton enthousiaste et professionnel. N'ajoute pas de commentaires, juste la traduction.";

            $translation = $openRouter->askQuestion($prompt);

            return $this->json([
                'success' => true,
                'translation' => $translation,
                'flag' => $languages[$targetLang]['flag'],
                'langName' => $languages[$targetLang]['name']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}