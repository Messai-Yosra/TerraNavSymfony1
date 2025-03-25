<?php

namespace App\Controller\interactions;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/ChatClient', name: 'app_chat')]
    public function index(): Response
    {
        return $this->render('interactions/chatClient.html.twig');
    }
}
