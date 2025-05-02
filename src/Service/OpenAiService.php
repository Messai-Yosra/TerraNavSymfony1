<?php
// src/Service/OpenAiService.php
namespace App\Service;

use OpenAI;
use OpenAI\Client;

class OpenAiService
{
    private Client $client;
    private const DEFAULT_MODEL = 'gpt-3.5-turbo';
    private const DEFAULT_TEMPERATURE = 0.7;
    private const DEFAULT_MAX_TOKENS = 100;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Génère des activités pour une destination
     */
    public function generateActivities(string $destination): array
    {
        $prompt = $this->buildActivitiesPrompt($destination);
        $response = $this->sendChatRequest($prompt);

        return $this->parseActivitiesResponse($response);
    }

    /**
     * Génère une description de voyage
     */
    public function generateVoyageDescription(array $voyageDetails): string
    {
        $prompt = $this->buildDescriptionPrompt($voyageDetails);
        $response = $this->sendChatRequest($prompt);

        return $response->choices[0]->message->content;
    }


    private function buildActivitiesPrompt(string $destination): string
    {
        return "Donne-moi 3 activités populaires à faire à $destination.
Pour chaque activité, fournis un JSON avec :
- name: nom court (max 5 mots)
- description: description concise (max 20 mots)


Format de sortie :
{
\"activities\": [
{
\"name\": \"...\",
\"description\": \"...\",
}
]
}";
    }

    private function buildDescriptionPrompt(array $voyageDetails): string
    {
        return sprintf(
            "Génère une description attrayante pour un voyage de %s à %s. " .
            "Le voyage se déroulera du %s au %s. " .
            "Type: %s, Prix: %.2f TND. " .
            "Places disponibles: %d. " .
            "Titre: %s. Fais des retours à la ligne.",
            $voyageDetails['pointDepart'],
            $voyageDetails['destination'],
            $voyageDetails['dateDepart'],
            $voyageDetails['dateRetour'],
            $voyageDetails['type'],
            $voyageDetails['prix'],
            $voyageDetails['nbPlaces'],
            $voyageDetails['titre']
        );
    }

    private function sendChatRequest(string $prompt): \OpenAI\Responses\Chat\CreateResponse
    {
        return $this->client->chat()->create([
            'model' => self::DEFAULT_MODEL,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => self::DEFAULT_TEMPERATURE,
            'max_tokens' => self::DEFAULT_MAX_TOKENS
        ]);
    }

    private function parseActivitiesResponse(\OpenAI\Responses\Chat\CreateResponse $response): array
    {
        $content = $response->choices[0]->message->content;

        // Essayez d'abord de parser directement le JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['activities'])) {
            return $decoded['activities'];
        }

        // Si échec, essayez d'extraire le JSON de la réponse
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \RuntimeException('Format de réponse inattendu: ' . $content);
        }

        $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Réponse JSON invalide: ' . json_last_error_msg());
        }

        return $decoded['activities'] ?? [];
    }
}