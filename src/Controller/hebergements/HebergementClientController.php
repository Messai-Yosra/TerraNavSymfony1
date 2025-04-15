<?php


namespace App\Controller\hebergements;

use App\Entity\Hebergement;
use App\Form\HebergementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HebergementClientController extends AbstractController
{
    #[Route('/HebergementsClient', name: 'app_hebergements')]
    public function index(): Response
    {
        return $this->render('hebergements/hebergementClient.html.twig');
    }

    #[Route('/FormAjout', name: 'formajout')]
    public function add(): Response
    {
        $hebergement = new Hebergement();
        $form = $this->createForm(HebergementType::class, $hebergement);

        return $this->render('hebergements/hebergement/AddHebergement.html.twig', [
            'form' => $form->createView()
        ]);
    }
}