<?php

namespace App\Controller\voyages;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoyageAdminController extends AbstractController
{
    #[Route('/VoyagesAdmin', name: 'admin_voyages')]
    public function index(): Response
    {
        return $this->render('voyages/voyageAdmin.html.twig');
    }
}
