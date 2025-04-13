<?php
// src/Service/LoginHistoryLogger.php

namespace App\Service\utilisateurs;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class LoginHistoryLogger
{
    private RequestStack $requestStack;
    private ParameterBagInterface $params;
    private SerializerInterface $serializer;
    private string $logDir;

    public function __construct(
        RequestStack $requestStack,
        ParameterBagInterface $params,
        SerializerInterface $serializer
    ) {
        $this->requestStack = $requestStack;
        $this->params = $params;
        $this->serializer = $serializer;
        $this->logDir = $params->get('kernel.project_dir') . '/var/log/login_history';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function logLogin(Utilisateur $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $data = [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'ip' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('referer'),
            'sessionId' => $request->getSession()->getId()
        ];

        // Créer le nom de fichier basé sur la date
        $filename = date('Y-m-d') . '.log';
        $filepath = $this->logDir . '/' . $filename;

        // Ajouter l'entrée au fichier
        $jsonData = json_encode($data) . "\n";
        file_put_contents($filepath, $jsonData, FILE_APPEND);
    }

    public function getLoginHistory(int $days = 30): array
    {
        $history = [];
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify("-$days days");
        
        // Pour le débogage - afficher la plage de dates
        $logDirectory = $this->logDir;
        $debugInfo = "Recherche dans $logDirectory du " . $startDate->format('Y-m-d') . " au " . $endDate->format('Y-m-d');
        error_log($debugInfo);
        
        // Approche alternative : parcourir directement le répertoire
        $allFiles = glob($this->logDir . '/*.log');
        error_log("Fichiers trouvés : " . implode(', ', $allFiles));
        
        foreach ($allFiles as $filepath) {
            error_log("Lecture du fichier: " . $filepath);
            
            $fileContent = file_get_contents($filepath);
            if ($fileContent === false) {
                error_log("Échec de lecture du fichier: " . $filepath);
                continue;
            }
            
            $lines = explode("\n", trim($fileContent));
            error_log("Nombre de lignes: " . count($lines));
            
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $data = json_decode($line, true);
                if ($data === null) {
                    error_log("Erreur décodage JSON: " . json_last_error_msg());
                    error_log("Ligne problématique: " . substr($line, 0, 100));
                } else {
                    $history[] = $data;
                }
            }
        }
        
        // Trier par timestamp décroissant
        usort($history, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        error_log("Total des entrées trouvées: " . count($history));
        return $history;
    }
    
    public function getUserLoginHistory(int $userId, int $days = 30): array
    {
        $allHistory = $this->getLoginHistory($days);
        return array_filter($allHistory, function($entry) use ($userId) {
            return $entry['userId'] === $userId;
        });
    }
}