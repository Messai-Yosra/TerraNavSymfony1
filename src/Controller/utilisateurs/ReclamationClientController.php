<?php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReclamationClientController extends AbstractController
{
    #[Route('/ReclamationsClient', name: 'app_reclamation')]
    public function index(): Response
    {
        return $this->render('utilisateurs/reclamantionClient.html.twig');
    }
}
