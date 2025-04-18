<?php

namespace App\Controller\hebergements;

use App\Entity\Chambre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class ChambreAdminController extends AbstractController
{
    #[Route('/chambresAdmin', name: 'admin_chambres')]
    public function index(EntityManagerInterface $em): Response
    {
        $chambres = $em->getRepository(Chambre::class)->findBy([], ['numero' => 'ASC']);

        return $this->render('hebergements/chambre/ChambreAdmin.html.twig', [ // Correction: .html.twig au lieu de .html.wig
            'chambres' => $chambres,
            'stats' => [
                'total' => count($chambres),
                'disponibles' => count(array_filter($chambres, fn($c) => $c->getDisponibilite()))
            ]
        ]);
    }
}