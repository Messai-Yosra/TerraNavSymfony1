<?php
// src/Controller/FrontController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'app_front', priority: 100)]
    public function index(): Response
    {
        // Afficher directement la page d'accueil sans redirection
        return $this->render('home/welcome.html.twig');
    }
}