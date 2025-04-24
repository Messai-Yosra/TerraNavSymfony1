<?php

namespace App\Controller\utilisateurs;

use App\Entity\Reclamation;
use App\Service\utilisateurs\TwilioSMSService;
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
    public function marquerTraite(Reclamation $reclamation, TwilioSMSService $smsService): Response
    {
        // Marquer comme traité
        $reclamation->setEtat('Traité');
        $this->entityManager->flush();
        
        // Récupérer le numéro de téléphone et l'email de l'utilisateur
        $utilisateur = $reclamation->getId_user();
        $phoneNumber = $utilisateur->getNumTel();
        $email = $utilisateur->getEmail(); // Récupérer l'email de l'utilisateur
        
        // Envoyer un SMS si le numéro existe
        if ($phoneNumber) {
            try {
                // Formater le numéro au format international
                if (!str_starts_with($phoneNumber, '+')) {
                    $phoneNumber = '+216' . preg_replace('/[^0-9]/', '', $phoneNumber);
                }
                
                // Envoyer le SMS avec l'email
                $result = $smsService->sendReclamationProcessedNotification($phoneNumber, $email);
                
                if ($result) {
                    $this->addFlash('success', 'Réclamation marquée comme traitée et SMS envoyé à l\'utilisateur');
                } else {
                    $this->addFlash('warning', 'Réclamation marquée comme traitée mais l\'envoi du SMS a échoué');
                }
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Réclamation marquée comme traitée mais erreur lors de l\'envoi du SMS: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('success', 'Réclamation marquée comme traitée (aucun numéro de téléphone disponible)');
        }
        
        return $this->redirectToRoute('admin_reclamation');
    }
    
    #[Route('/details/{id}', name: 'admin_reclamation_details')]
    public function details(Reclamation $reclamation): Response
    {
        return $this->render('utilisateurs/reclamationAdmin.html.twig', [
            'reclamation' => $reclamation,
            'showDetails' => true
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
