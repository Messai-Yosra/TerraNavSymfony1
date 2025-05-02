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
}