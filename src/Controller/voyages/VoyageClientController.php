<?php
// src/Controller/VoyageClientController.php

namespace App\Controller\voyages;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoyageClientController extends AbstractController
{
    #[Route('/VoyagesClient', name: 'app_voyages')]
    public function index(): Response
    {
        return $this->render('voyages/voyageClient.html.twig');
    }
}