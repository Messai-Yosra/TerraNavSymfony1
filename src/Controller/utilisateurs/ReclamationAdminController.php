<?php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamationAdminController extends AbstractController
{
    #[Route('/ReclamationsAdmin', name: 'admin_reclamation')]
    public function index(): Response
    {
        return $this->render('utilisateurs/reclamationAdmin.html.twig');
    }
}
