<?php
// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de login
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        // Redirection directe vers le dashboard client sans condition
        return $this->redirectToRoute('app_dashboardClient');
    }
    
    #[Route('/home', name: 'app_home_client')]
    public function homeClient(): Response
    {
        // Redirection directe vers le dashboard client
        return $this->redirectToRoute('app_dashboardClient');
    }
}