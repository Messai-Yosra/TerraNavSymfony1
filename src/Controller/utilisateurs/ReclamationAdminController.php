<?php

namespace App\Controller\utilisateurs;

use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reclamations')]
class ReclamationAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_reclamations_index')]
    #[Route('/', name: 'admin_reclamation')] // Ajoutez cette ligne comme alias
    public function index(): Response
    {
        $reclamations = $this->entityManager->getRepository(Reclamation::class)
            ->findBy([], ['dateReclamation' => 'DESC']);
        
        return $this->render('utilisateurs/reclamationAdmin.html.twig', [
            'reclamations' => $reclamations
        ]);
    }
    
    #[Route('/marquer-traite/{id}', name: 'admin_reclamation_traiter')]
    public function marquerTraite(Reclamation $reclamation): Response
    {
        // Marquer comme traité
        $reclamation->setEtat('Traité');
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Réclamation #' . $reclamation->getId() . ' marquée comme traitée');
        return $this->redirectToRoute('admin_reclamation');
    }
    
    #[Route('/details/{id}', name: 'admin_reclamation_details')]
    public function details(Reclamation $reclamation): Response
    {
        // Modifiez cette ligne pour qu'elle pointe vers le bon template
        return $this->render('utilisateurs/reclamationAdmin.html.twig', [
            'reclamation' => $reclamation,
            'showDetails' => true  // Ajoutez ce flag
        ]);
    }
}
