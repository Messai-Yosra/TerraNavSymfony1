<?php
// src/Controller/ProfileController.php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/ProfileClient', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('utilisateurs/profileClient.html.twig');
    }
}