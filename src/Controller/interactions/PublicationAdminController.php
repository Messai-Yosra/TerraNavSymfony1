<?php

namespace App\Controller\interactions;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicationAdminController extends AbstractController
{
    #[Route('/PublicationsAdmin', name: 'admin_publications')]
    public function index(): Response
    {
        return $this->render('interactions/publicationAdmin.html.twig');
    }
}
