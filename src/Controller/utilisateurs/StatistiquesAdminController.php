<?php

namespace App\Controller\utilisateurs;

use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/statistiques')]
class StatistiquesAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_statistiques')]
    public function index(): Response
    {
        // Statistiques des utilisateurs
        $totalUtilisateurs = $this->entityManager->getRepository(Utilisateur::class)->count([]);
        $totalClients = $this->entityManager->getRepository(Utilisateur::class)->count(['role' => 'client']);
        $totalAgences = $this->entityManager->getRepository(Utilisateur::class)->count(['role' => 'agence']);
        
        // Statistiques des réclamations
        $totalReclamations = $this->entityManager->getRepository(Reclamation::class)->count([]);
        $reclamationsTraitees = $this->entityManager->getRepository(Reclamation::class)->count(['etat' => 'Traité']);
        $reclamationsNonTraitees = $totalReclamations - $reclamationsTraitees;

        // Données pour les graphiques mensuels
        $reclamationsParMois = $this->getReclamationsParMois();
        $inscriptionsParMois = $this->getInscriptionsParMois();
        
        return $this->render('utilisateurs/statistiquesAdmin.html.twig', [
            'totalUtilisateurs' => $totalUtilisateurs,
            'totalClients' => $totalClients,
            'totalAgences' => $totalAgences,
            'totalReclamations' => $totalReclamations,
            'reclamationsTraitees' => $reclamationsTraitees,
            'reclamationsNonTraitees' => $reclamationsNonTraitees,
            'reclamationsParMois' => json_encode($reclamationsParMois),
            'inscriptionsParMois' => json_encode($inscriptionsParMois)
        ]);
    }
    
    
    
    private function getInscriptionsParMois(): array
    {
        // Générer des données fictives pour le graphique d'inscriptions
        $months = [];
        
        // Générer des données pour les 12 derniers mois
        for ($i = 1; $i <= 12; $i++) {
            $months[] = [
                'mois' => $i,
                'total' => rand(5, 20) // Valeurs aléatoires entre 5 et 20
            ];
        }
        
        return $months;
    }

    private function getReclamationsParMois(): array
    {
        $conn = $this->entityManager->getConnection();
        
        // Correction: Utiliser dateReclamation au lieu de date_reclamation
        $sql = "
            SELECT MONTH(dateReclamation) as mois, COUNT(*) as total
            FROM reclamation
            WHERE dateReclamation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY MONTH(dateReclamation)
            ORDER BY mois
        ";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }
}