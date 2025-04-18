<?php

namespace App\Controller\hebergements;

use App\Entity\Chambre;
use App\Entity\Hebergement;
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

        $hebergements = $em->getRepository(Hebergement::class)->findBy([], ['nom' => 'ASC']);

        $stats = [
            'total' => count($chambres),
            'disponibles' => count(array_filter($chambres, fn($c) => $c->getDisponibilite())),
            // Distribution by hébergement
            'hebergements' => $em->createQueryBuilder()
                ->select('h.nom AS hebergement, COUNT(c.id) AS count')
                ->from(Chambre::class, 'c')
                ->join('c.id_hebergement', 'h')
                ->groupBy('h.id')
                ->orderBy('count', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),
            // Distribution by capacity
            'capacites' => $em->createQueryBuilder()
                ->select('c.capacite AS capacite, COUNT(c.id) AS count')
                ->from(Chambre::class, 'c')
                ->where('c.capacite IS NOT NULL')
                ->groupBy('c.capacite')
                ->orderBy('c.capacite', 'ASC')
                ->getQuery()
                ->getResult(),
            // Average price by hébergement
            'prix' => $em->createQueryBuilder()
                ->select('h.nom AS hebergement, AVG(c.prix) AS avg_prix')
                ->from(Chambre::class, 'c')
                ->join('c.id_hebergement', 'h')
                ->where('c.prix IS NOT NULL')
                ->groupBy('h.id')
                ->orderBy('avg_prix', 'DESC')
                ->getQuery()
                ->getResult(),
        ];

        return $this->render('hebergements/chambre/ChambreAdmin.html.twig', [
            'chambres' => $chambres,
            'hebergements' => $hebergements,
            'stats' => $stats,
        ]);
    }
}