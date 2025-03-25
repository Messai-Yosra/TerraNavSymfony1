<?php
// src/Controller/ReservationController.php

namespace App\Controller\reservations;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PanierController extends AbstractController
{
    #[Route('/PanierClient', name: 'app_panier')]
    public function index(): Response
    {
        return $this->render('reservations/panierClient.html.twig');
    }
}