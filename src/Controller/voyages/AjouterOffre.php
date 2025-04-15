<?php

namespace App\Controller\voyages;

use App\Repository\Voyage\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjouterOffre extends AbstractController
{
    #[Route('/AjouterOffre', name: 'app_ajouter_offre')]
    public function AjouterOffre(): Response
    {
        return $this->render('voyages/AjouterOffre.html.twig');
    }
}