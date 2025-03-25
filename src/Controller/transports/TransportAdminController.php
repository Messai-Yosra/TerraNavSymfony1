<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TransportAdminController extends AbstractController
{
    #[Route('/TransportsAdmin', name: 'admin_transports')]
    public function index(): Response
    {
        return $this->render('transports/transportAdmin.html.twig');
    }
}
