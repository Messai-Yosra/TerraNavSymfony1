<?php
// src/Service/OpenAiService.php
// src/Service/PostDescriptionGenerator.php
namespace App\Service\interactions;

use OpenAI;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PostDescriptionGenerator
{
    private Client $client;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct(
        ParameterBagInterface $params,
        string $model = 'gpt-3.5-turbo',
        float $temperature = 0.7,
        int $maxTokens = 150
    ) {
        $apiKey = $params->get('openai_api_key');
        $this->client = OpenAI::client($apiKey);
        $this->model = $model;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
    }

    /**
     * Génère une description de post basée sur le contenu fourni
     */
    public function generatePostDescription(
        string $postContent,
        string $postType = 'general',
        ?string $authorStyle = null,
        bool $includeHashtags = true
    ): string {
        $prompt = $this->buildPostPrompt($postContent, $postType, $authorStyle, $includeHashtags);
        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un assistant qui aide à rédiger des descriptions engageantes pour des posts sociaux.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens
        ]);

        return trim($response->choices[0]->message->content);
    }

    private function buildPostPrompt(
        string $content,
        string $postType,
        ?string $style,
        bool $hashtags
    ): string {
        // Utiliser le contenu fourni comme base
        $prompt = "Améliore et reformule ce contenu pour un post social : \"$content\"\n\n";
        $prompt .= "La description doit :\n";
        $prompt .= "- Garder l'essence du message original\n";
        $prompt .= "- Être concise (1-3 phrases max)\n";
        $prompt .= "- Captiver l'attention\n sans utiliser des emojis";

        if ($hashtags) {
            $prompt .= "- Inclure 3 hashtags pertinents à la fin\n";
        }

        return $prompt;
    }

    /**
     * Génère plusieurs options de description
     */
    public function generateDescriptionOptions(
        string $postContent,
        int $variations = 3
    ): array {
        $options = [];
        for ($i = 0; $i < $variations; $i++) {
            $options[] = $this->generatePostDescription(
                $postContent,
                'general',
                null,
                true
            );
        }
        return $options;
    }

    /**
     * Améliore une description existante
     */
    public function improveDescription(
        string $currentDescription,
        string $direction = 'more_engaging'
    ): string {
        $prompt = "Améliore cette description de post pour la rendre plus ";
        $prompt .= match($direction) {
            'more_engaging' => "engageante et accrocheuse",
            'more_formal' => "formelle et professionnelle",
            'more_casual' => "décontractée et amicale",
            default => "percutante"
        };
        $prompt .= ":\n\n\"$currentDescription\"";

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->temperature + 0.1, // Un peu plus créatif
            'max_tokens' => $this->maxTokens
        ]);

        return trim($response->choices[0]->message->content);
    }
}