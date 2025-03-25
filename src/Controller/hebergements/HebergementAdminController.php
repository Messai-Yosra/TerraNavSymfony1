<?php

namespace App\Controller\hebergements;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HebergementAdminController extends AbstractController
{
    #[Route('/HebergementsAdmin', name: 'admin_hebergements')]
    public function index(): Response
    {
        return $this->render('hebergements/hebergementAdmin.html.twig');
    }
}
