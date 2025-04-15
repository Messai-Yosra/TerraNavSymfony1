<?php

namespace App\Controller\reservations;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;

final class ReservationAdminController extends AbstractController
{
    #[Route('/ReservationAdmin', name: 'admin_reservations')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get all Client users with their paniers and reservations
        $users = $entityManager->getRepository(Utilisateur::class)->createQueryBuilder('u')
            ->leftJoin('u.paniers', 'p')
            ->leftJoin('p.reservations', 'r')
            ->where('u.role = :role')
            ->setParameter('role', 'Client')
            ->addSelect('p')
            ->addSelect('r')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('reservations/reservationAdmin.html.twig', [
            'users' => $users
        ]);
    }
}