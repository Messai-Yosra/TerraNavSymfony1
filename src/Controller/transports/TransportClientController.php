<?php
// src/Controller/TransportClientController.php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TransportClientController extends AbstractController
{
    #[Route('/TransportsClient', name: 'app_transports')]
    public function index(): Response
    {
        return $this->render('transports/trajetClient.html.twig');
    }
}