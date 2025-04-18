<?php

namespace App\Controller\hebergements;

use App\Entity\Hebergement;
use App\Entity\Chambre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HebergementAdminController extends AbstractController
{
    #[Route('/HebergementsAdmin', name: 'admin_hebergements')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 9;

        // Fetch hebergements
        $query = $em->createQueryBuilder()
            ->select('h')
            ->from(Hebergement::class, 'h')
            ->leftJoin('h.chambres', 'c')
            ->leftJoin('c.images', 'i')
            ->addSelect('c', 'i')
            ->orderBy('h.nom', 'ASC')
            ->getQuery();

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $total = count($paginator);

        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Fetch distinct villes
        $villes = $em->createQueryBuilder()
            ->select('DISTINCT h.ville')
            ->from(Hebergement::class, 'h')
            ->orderBy('h.ville', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        // Statistics data
        $stats = [
            // Top 10 villes
            'villes' => $em->createQueryBuilder()
                ->select('h.ville AS ville, COUNT(h.id) AS count')
                ->from(Hebergement::class, 'h')
                ->groupBy('h.ville')
                ->orderBy('count', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),

            // Notes distribution
            'notes' => $em->createQueryBuilder()
                ->select("
                    CASE
                        WHEN h.note_moyenne < 1 THEN '0-1'
                        WHEN h.note_moyenne < 2 THEN '1-2'
                        WHEN h.note_moyenne < 3 THEN '2-3'
                        WHEN h.note_moyenne < 4 THEN '3-4'
                        ELSE '4-5'
                    END AS range,
                    COUNT(h.id) AS count
                ")
                ->from(Hebergement::class, 'h')
                ->groupBy('range')
                ->orderBy('range', 'ASC')
                ->getQuery()
                ->getResult(),

            // Types distribution
            'types' => $em->createQueryBuilder()
                ->select('h.type_hebergement, COUNT(h.id) AS count, AVG(c.prix) AS avg_prix')
                ->from(Hebergement::class, 'h')
                ->leftJoin('h.chambres', 'c')
                ->groupBy('h.type_hebergement')
                ->orderBy('count', 'DESC')
                ->getQuery()
                ->getResult(),

            // Capacites distribution
            'capacites' => $em->createQueryBuilder()
                ->select('c.capacite, COUNT(c.id) AS count')
                ->from(Chambre::class, 'c')
                ->where('c.capacite IS NOT NULL')
                ->groupBy('c.capacite')
                ->orderBy('c.capacite', 'ASC')
                ->getQuery()
                ->getResult(),
        ];

        return $this->render('hebergements/hebergementAdmin.html.twig', [
            'hebergements' => $paginator,
            'villes' => $villes,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ]);
    }
}