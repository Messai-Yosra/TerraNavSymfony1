<?php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/DashboardClient', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('utilisateurs/dashboardAdmin.html.twig');
    }
}
