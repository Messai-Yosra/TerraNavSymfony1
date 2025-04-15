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

  
    #[Route('/', name: 'admin_reclamation')]
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

    #[Route('/export/csv', name: 'admin_reclamation_export_csv')]
    public function exportCsv(): Response
    {
        // Récupérer toutes les réclamations
        $reclamations = $this->entityManager->getRepository(Reclamation::class)
            ->findBy([], ['dateReclamation' => 'DESC']);
        
        // Créer le contenu CSV
        $csvContent = "ID,Utilisateur,Sujet,Description,Date,État\n"; // En-têtes du fichier CSV
        
        foreach ($reclamations as $reclamation) {
            // Échapper les virgules et les retours à la ligne dans la description
            $description = str_replace(["\n", "\r", ","], [" ", " ", ";"], $reclamation->getDescription());
            
            $csvContent .= sprintf(
                "%d,%s,%s,%s,%s,%s\n",
                $reclamation->getId(),
                $reclamation->getId_user()->getNom(), // Nom de l'utilisateur
                str_replace(",", ";", $reclamation->getSujet()), // Remplacer les virgules par des points-virgules
                $description,
                $reclamation->getDateReclamation() ? $reclamation->getDateReclamation()->format('Y-m-d H:i:s') : 'N/A',
                $reclamation->getEtat()
            );
        }
        
        // Convertir le contenu en UTF-8 pour gérer les caractères spéciaux
        $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'auto');
        
        // Créer une réponse HTTP avec le contenu CSV
        $response = new Response($csvContent);
        
        // Définir les en-têtes pour le téléchargement du fichier
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="reclamations_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}
