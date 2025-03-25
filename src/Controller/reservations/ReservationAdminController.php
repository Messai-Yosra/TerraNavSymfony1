<?php

namespace App\Controller\reservations;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationAdminController extends AbstractController
{
    #[Route('/ReservationAdmin', name: 'admin_reservations')]
    public function index(): Response
    {
        return $this->render('reservations/reservationAdmin.html.twig');
    }
}
