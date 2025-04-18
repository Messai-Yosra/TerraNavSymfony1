<?php
// src/Controller/GoogleController.php
namespace App\Controller\utilisateurs;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Rediriger vers Google
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile' // Les scopes demandés
            ], []);  // Additional parameters
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): RedirectResponse
    {
        // Cette méthode ne sera jamais exécutée
        return $this->redirectToRoute('app_home');
    }
}