<?php
// src/Controller/voyages/OffreAIController.php
namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Form\voyages\OffreType;
use App\Repository\Voyage\OffreRepository;
use App\Service\OpenRouterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreAIController extends AbstractController
{
    #[Route('/agence/offres/generer-suggestion', name: 'app_generate_offre_suggestion', methods: ['POST'])]
    public function generateSuggestion(Request $request, OffreRepository $offreRepository, OpenRouterService $openRouter): Response
    {
// Récupérer toutes les offres existantes pour analyse
        $offres = $offreRepository->findAll();

// Préparer les données pour l'IA
        $titres = [];
        $reductions = [];
        $datesDebut = [];
        $datesFin = [];

        foreach ($offres as $offre) {
            $titres[] = $offre->getTitre();
            $reductions[] = $offre->getReduction();
            $datesDebut[] = $offre->getDateDebut()->format('Y-m-d');
            $datesFin[] = $offre->getDateFin()->format('Y-m-d');
        }

// Calculer la réduction moyenne
        $reductionMoyenne = count($reductions) > 0 ? array_sum($reductions) / count($reductions) : 0;

// Demander à l'IA de générer une suggestion
        $prompt = $this->createSuggestionPrompt($titres, $reductions, $datesDebut, $datesFin, $reductionMoyenne);
        $suggestion = $openRouter->askQuestion($prompt);

// Parser la réponse de l'IA (format JSON)
        try {
            $data = json_decode($suggestion, true);

            // Créer un objet Offre temporaire
            $offre = new Offre();
            $offre->setTitre($data['titre'] ?? 'Nouvelle Offre');
            $offre->setDescription($data['description'] ?? 'Description générée par IA');
            $offre->setReduction($data['reduction'] ?? 15); // 15% par défaut

            // Utiliser les dates de l'IA si disponibles, sinon valeurs par défaut
            $dateDebut = isset($data['dateDebut']) ? new \DateTime($data['dateDebut']) : new \DateTime('tomorrow');
            $dateFin = isset($data['dateFin']) ? new \DateTime($data['dateFin']) : (new \DateTime('tomorrow'))->modify('+7 days');

            $offre->setDateDebut($dateDebut);
            $offre->setDateFin($dateFin);

            return $this->render('voyages/_offre_suggestion_content.html.twig', [
                'offre' => $offre,
                'suggestion_text' => $data['explication'] ?? 'Voici une suggestion basée sur vos offres existantes',
                'offre_data' => [
                    'titre' => $offre->getTitre(),
                    'description' => $offre->getDescription(),
                    'reduction' => $offre->getReduction(),
                    'dateDebut' => $dateDebut->format('Y-m-d\TH:i'), // Format compatible avec datetime-local
                    'dateFin' => $dateFin->format('Y-m-d\TH:i')      // Format compatible avec datetime-local
                ]
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur lors de la génération de la suggestion: " . $e->getMessage(), 400);
        }
    }

    private function createSuggestionPrompt(array $titres, array $reductions, array $datesDebut, array $datesFin, float $reductionMoyenne): string
    {
        $titresStr = implode(', ', array_unique($titres));
        $reductionsStr = implode(', ', $reductions);
        $datesDebutStr = implode(', ', $datesDebut);
        $datesFinStr = implode(', ', $datesFin);

        return <<<PROMPT
    Tu es un expert en création d'offres promotionnelles pour des agences de voyage. Propose une idée d'offre innovante qui complète l'offre existante.

Voici les données actuelles:
- Titres existants: $titresStr
- Réductions existantes: $reductionsStr (%)
- Dates de début existantes: $datesDebutStr
- Dates de fin existantes: $datesFinStr
- Réduction moyenne: $reductionMoyenne %

Génère une suggestion d'offre qui:
1. Ne répète pas exactement les titres existants
2. Propose une réduction attractive mais réaliste
3. A une période de validité cohérente
4. Est originale et attrayante pour les clients

Retourne une réponse au format JSON avec ces champs:
{
"titre": "Un titre attractif pour l'offre",
"description": "Une description détaillée et attractive (50-100 mots)",
"reduction": "Le pourcentage de réduction suggéré (entre 5 et 50)",
"dateDebut": "La date de début suggérée (format YYYY-MM-DD)",
"dateFin": "La date de fin suggérée (format YYYY-MM-DD)",
"explication": "Une brève explication de pourquoi cette suggestion est pertinente"
}

La réponse doit être uniquement le JSON, sans commentaires avant ou après.
PROMPT;
    }

    
#[Route('/AjoutOffreIA', name: 'app_ajout_offre_ia')]
public function ajoutOffreIA(Request $request): Response
{
    $offre = new Offre();

    // Pré-remplir avec les paramètres de l'URL
    $offre->setTitre($request->query->get('titre', 'Nouvelle Offre'));
    $offre->setDescription($request->query->get('description', 'Description générée par IA'));
    $offre->setReduction($request->query->get('reduction', 15)); // Réduction par défaut : 15%

    // Initialiser les dates
    if ($dateDebut = $request->query->get('dateDebut')) {
        $offre->setDateDebut(new \DateTime($dateDebut));
    } else {
        $offre->setDateDebut(new \DateTime('tomorrow')); // Par défaut : demain
    }

    if ($dateFin = $request->query->get('dateFin')) {
        $offre->setDateFin(new \DateTime($dateFin));
    } else {
        $offre->setDateFin((new \DateTime('tomorrow'))->modify('+7 days')); // Par défaut : 7 jours après demain
    }

    return $this->render('voyages/AjouterOffreIA.html.twig', [
        'offre' => $offre,
        'isIaGenerated' => true
    ]);
}
}