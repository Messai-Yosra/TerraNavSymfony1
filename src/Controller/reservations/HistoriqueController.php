<?php

namespace App\Controller\reservations;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HistoriqueController extends AbstractController
{
    #[Route('/HistoriqueClient', name: 'app_historique')]
    public function index(): Response
    {
        return $this->render('reservations/historiqueClient.html.twig');
    }
}
