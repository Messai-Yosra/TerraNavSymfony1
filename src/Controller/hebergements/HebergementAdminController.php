<?php

namespace App\Controller\hebergements;

use App\Entity\Hebergement;
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
        $limit = 9; // 9 hébergements par page
        
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

        return $this->render('hebergements/hebergementAdmin.html.twig', [
            'hebergements' => $paginator,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ]);
    }
}