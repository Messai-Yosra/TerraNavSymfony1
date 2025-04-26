<?php
// src/Controller/voyages/VoyageAIController.php
namespace App\Controller\voyages;

use App\Entity\Voyage;
use App\Repository\Voyage\VoyageRepository;
use App\Service\OpenRouterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VoyageAIController extends AbstractController
{
    #[Route('/agence/voyages/generer-suggestion', name: 'app_generate_voyage_suggestion', methods: ['POST'])]
    public function generateSuggestion(Request $request, VoyageRepository $voyageRepository, OpenRouterService $openRouter): Response
    {
        // Récupérer tous les voyages existants pour analyse
        $voyages = $voyageRepository->findAll();

        // Préparer les données pour l'IA
        $destinations = [];
        $types = [];
        $prixMoyen = 0;

        foreach ($voyages as $voyage) {
            $destinations[] = $voyage->getDestination();
            $types[] = $voyage->getType();
            $prixMoyen += $voyage->getPrix();
        }

        $prixMoyen = count($voyages) > 0 ? $prixMoyen / count($voyages) : 0;

        // Demander à l'IA de générer une suggestion
        $prompt = $this->createSuggestionPrompt(array_unique($destinations), array_unique($types), $prixMoyen);
        $suggestion = $openRouter->askQuestion($prompt);

        // Parser la réponse de l'IA (format JSON)
        try {
            $data = json_decode($suggestion, true);

            // Créer un objet Voyage temporaire
            $voyage = new Voyage();
            $voyage->setTitre($data['titre'] ?? 'Nouveau Voyage');
            $voyage->setDestination($data['destination'] ?? 'Destination inconnue');
            $voyage->setPointDepart($data['point_depart'] ?? 'Paris');
            $voyage->setType($data['type'] ?? 'Aventure');
            $voyage->setPrix($data['prix'] ?? 500);
            $voyage->setNbPlacesD($data['nb_places'] ?? 20);
            $voyage->setDescription($data['description'] ?? 'Description générée par IA');

            // Dates par défaut (7 jours à partir de demain)
            $dateDepart = new \DateTime('tomorrow');
            $dateRetour = (new \DateTime('tomorrow'))->modify('+7 days');

            $voyage->setDateDepart($dateDepart);
            $voyage->setDateRetour($dateRetour);

            return $this->render('voyages/_voyage_suggestion_content.html.twig', [
                'voyage' => $voyage,
                'suggestion_text' => $data['explication'] ?? 'Voici une suggestion basée sur vos voyages existants'
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur lors de la génération de la suggestion: ".$e->getMessage(), 400);
        }
    }

    private function createSuggestionPrompt(array $destinations, array $types, float $prixMoyen): string
    {
        $destinationsStr = implode(', ', $destinations);
        $typesStr = implode(', ', $types);

        return <<<PROMPT
Tu es un expert en création de voyages touristiques. Propose une idée de voyage innovante qui complète l'offre existante d'une agence.

Voici les données actuelles:
- Destinations existantes: $destinationsStr
- Types de voyages existants: $typesStr
- Prix moyen: $prixMoyen DT

Génère une suggestion de voyage qui:
1. Ne répète pas exactement les destinations existantes
2. Propose une expérience différente
3. Est réaliste et attrayante pour les clients

Retourne une réponse au format JSON avec ces champs:
{
    "titre": "Un titre simple ,exemple:TUN-PAR pour un voyage de tunis vers paris",
    "destination": "La destination proposée",
    "point_depart": "La ville de départ",
    "type": "Le type de voyage",
    "prix": "Le prix suggéré",
    "nb_places": "Le nombre de places",
    "description": "Une description détaillée et attractive (100 mots)",
    "explication": "Une brève explication de pourquoi cette suggestion est pertinente"
}

La réponse doit être uniquement le JSON, sans commentaires avant ou après.
PROMPT;
    }


}