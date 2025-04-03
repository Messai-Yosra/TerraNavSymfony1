<?php


namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BienvenueAgenceController extends AbstractController
{
    #[Route('/Bienvenue', name: 'app_bienvenue_agence')]
    public function Bienvenue(): Response
    {
        return $this->render('voyages/BienvenueAgence.html.twig');
    }

    #[Route('/statsAgence', name: 'app_stats_agence')]
    public function VoirStats(): Response
    {
        return $this->render('voyages/StatistiqueAgence.html.twig');
    }



}