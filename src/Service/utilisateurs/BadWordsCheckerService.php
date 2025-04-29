<?php
// filepath: src/Service/BadWordsCheckerService.php
namespace App\Service\utilisateurs;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BadWordsCheckerService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Vérifie si le texte contient des mots inappropriés
     * @return bool true si le texte est propre, false s'il contient des mots inappropriés
     */
    public function checkText(string $text): bool
    {
        $encodedText = urlencode($text);
        $apiUrl = "https://www.purgomalum.com/service/containsprofanity?text=" . $encodedText;

        try {
            $response = $this->httpClient->request('GET', $apiUrl);
            $result = $response->getContent();
            
            // L'API renvoie "true" si des mots inappropriés sont détectés
            return $result === "false";
        } catch (\Exception $e) {
            // En cas d'erreur, on laisse passer le texte
            return true;
        }
    }

    /**
     * Censure les mots inappropriés dans un texte
     */
    public function censorText(string $text): string
    {
        $encodedText = urlencode($text);
        $apiUrl = "https://www.purgomalum.com/service/plain?text=" . $encodedText . "&fill_char=*";

        try {
            $response = $this->httpClient->request('GET', $apiUrl);
            return $response->getContent();
        } catch (\Exception $e) {
            // En cas d'erreur, on retourne le texte original
            return $text;
        }
    }
}