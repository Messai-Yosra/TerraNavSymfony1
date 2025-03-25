<?php
// src/Controller/HebergementClientController.php

namespace App\Controller\hebergements;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HebergementClientController extends AbstractController
{
    #[Route('/HebergementsClient', name: 'app_hebergements')]
    public function index(): Response
    {
        return $this->render('hebergements/hebergementClient.html.twig');
    }
}