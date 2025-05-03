<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterService
{
    private $client;
    private $apiKey;
    private $baseUrl = "https://openrouter.ai/api/v1";
    private $defaultHeaders = [];

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->defaultHeaders = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function setSiteInfo(string $siteUrl = null, string $siteName = null): void
    {
        if ($siteUrl) {
            $this->defaultHeaders['HTTP-Referer'] = $siteUrl;
        }
        if ($siteName) {
            $this->defaultHeaders['X-Title'] = $siteName;
        }
    }

    public function getChatCompletion(string $model, array $messages, array $extraBody = []): array
    {
        $data = array_merge($extraBody, [
            'model' => $model,
            'messages' => $messages,
        ]);

        $response = $this->client->request('POST', $this->baseUrl.'/chat/completions', [
            'headers' => $this->defaultHeaders,
            'json' => $data,
        ]);

        return $response->toArray();
    }

    public function askQuestion(string $question, string $model = "microsoft/mai-ds-r1:free"): string
    {
        $messages = [
            [
                'role' => 'user',
                'content' => $question
            ]
        ];

        $response = $this->getChatCompletion($model, $messages);

        return $response['choices'][0]['message']['content'];
    }

    // src/Service/OpenRouterService.php

    public function analyzeImagesAndGenerateDescription(array $imageUrls, string $destination, string $departure, string $type): string
    {
        // Construire le prompt avec les informations du voyage
        $prompt = "Tu es un expert en voyages. Analyse ces images et crée une description attrayante pour un voyage de type '$type' ";
        $prompt .= "allant de $departure à $destination. La description doit être en français, entre 100 et 150 mots, ";
        $prompt .= "avec un ton enthousiaste et professionnel. Mets en valeur les points forts visibles dans les images.";

        // Préparer les messages pour l'API
        $messages = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ]
                ]
            ]
        ];

        // Ajouter chaque image aux messages
        foreach ($imageUrls as $imageUrl) {
            $messages[0]['content'][] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl
                ]
            ];
        }

        // Appeler l'API
        $response = $this->getChatCompletion('anthropic/claude-3-haiku', $messages);

        return $response['choices'][0]['message']['content'];
    }
}