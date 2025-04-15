<?php

namespace App\Controller\utilisateurs;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UtilisateurType;
use App\Service\utilisateurs\LoginHistoryLogger;

final class DashboardController extends AbstractController
{
    #[Route('/DashboardClient', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les utilisateurs de la base de données
        $utilisateurs = $entityManager->getRepository(Utilisateur::class)->findAll();
        
        // Dans votre contrôleur avant de passer les utilisateurs au template
        $utilisateursFiltres = array_map(function($u) {
            if ($u->getPrenom() === null) $u->setPrenom('');
            if ($u->getNom() === null) $u->setNom('');
            return $u;
        }, $utilisateurs);

        return $this->render('utilisateurs/dashboardAdmin.html.twig', [
            'utilisateurs' => $utilisateursFiltres,
        ]);
    }
    
    #[Route('/admin/user/{id}', name: 'admin_user_show')]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateurs/show.html.twig', [
            'utilisateur' => $utilisateur
        ]);
    }
    
    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit')]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire pour l'utilisateur
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder les modifications
            $entityManager->flush();

            // Ajouter un message de succès
            $this->addFlash('success', 'Utilisateur modifié avec succès!');

            // Rediriger vers le dashboard
            return $this->redirectToRoute('admin_dashboard');
        }

        // Rendre la vue du formulaire
        return $this->render('utilisateurs/edit.html.twig', [
            'form' => $form->createView(),
            'utilisateur' => $utilisateur,
        ]);
    }
    
    // Nouvelle méthode de suppression avec plus de debug
    #[Route('/admin/user/delete/{id}', name: 'admin_user_delete', methods: ['GET'])]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        // Log de débogage
        error_log("Tentative de suppression de l'utilisateur ID: $id");
        
        try {
            // Récupérer l'utilisateur par ID
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
            
            if (!$utilisateur) {
                $this->addFlash('danger', "Utilisateur avec ID $id non trouvé");
                return $this->redirectToRoute('admin_dashboard');
            }
            
            // Log juste avant suppression
            error_log("Suppression de: " . $utilisateur->getEmail());
            
            // Effectuer la suppression
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            
            // Message de succès
            $this->addFlash('success', 'Utilisateur supprimé avec succès!');
        } catch (\Exception $e) {
            // Capture de toute exception
            $errorMsg = $e->getMessage();
            error_log("ERREUR suppression: $errorMsg");
            $this->addFlash('danger', "Erreur lors de la suppression: $errorMsg");
        }
        
        // Toujours rediriger vers le dashboard
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/user/{id}/update', name: 'admin_user_update', methods: ['POST'])]
    public function updateUser(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les données du formulaire
        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');
        $email = $request->request->get('email');
        $role = $request->request->get('role');
        
        // Mettre à jour l'utilisateur
        $utilisateur->setPrenom($prenom);
        $utilisateur->setNom($nom);
        $utilisateur->setEmail($email);
        $utilisateur->setRole($role);
        
        // Enregistrer les modifications
        $entityManager->flush();
        
        // Retourner une réponse JSON
        return $this->json([
            'success' => true,
            'message' => 'Utilisateur modifié avec succès'
        ]);
    }

    #[Route('/admin/users/search', name: 'admin_users_search', methods: ['GET'])]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Log that we hit this route
        error_log('SEARCH ROUTE HIT at ' . date('Y-m-d H:i:s'));
        
        // Add cache prevention headers
        $response = new Response();
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        $query = $request->query->get('q', '');
        error_log('Search query: "' . $query . '"');
        
        try {
            $repository = $entityManager->getRepository(Utilisateur::class);
            
            // Search in multiple fields
            $qb = $repository->createQueryBuilder('u');
            
            if (!empty($query)) {
                $qb->where('u.prenom LIKE :query')
                   ->orWhere('u.nom LIKE :query')
                   ->orWhere('u.email LIKE :query')
                   ->orWhere('u.username LIKE :query')
                   ->orWhere('u.role LIKE :query')
                   ->setParameter('query', '%' . $query . '%');
            }
            
            $utilisateurs = $qb->getQuery()->getResult();
            
            // Debug output
            error_log('Search results count: ' . count($utilisateurs));
            
            // Render only the user cards HTML
            $content = $this->renderView('utilisateurs/_user_cards_partial.html.twig', [
                'utilisateurs' => $utilisateurs
            ]);
            
            $response->setContent($content);
            return $response;
        } catch (\Exception $e) {
            // Log any errors that occur
            error_log('ERROR in search: ' . $e->getMessage());
            
            // Return a proper error response
            $response->setContent('<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>');
            $response->setStatusCode(500);
            return $response;
        }
    }

    #[Route('/admin/historique-connexions', name: 'admin_login_history')]
    public function loginHistory(LoginHistoryLogger $loginLogger): Response
    {
        // Récupérer l'historique des 30 derniers jours
        $loginHistory = $loginLogger->getLoginHistory(30);
        
        // Analyser et préparer des statistiques
        $stats = [
            'totalConnexions' => count($loginHistory),
            'connexionsAujourdhui' => count(array_filter($loginHistory, function($entry) {
                return substr($entry['timestamp'], 0, 10) === date('Y-m-d');
            })),
            'utilisateursUniques' => count(array_unique(array_column($loginHistory, 'userId'))),
            'connexionsParRole' => []
        ];
        
        // Agréger les connexions par rôle
        foreach ($loginHistory as $entry) {
            $role = $entry['role'] ?? 'unknown';
            if (!isset($stats['connexionsParRole'][$role])) {
                $stats['connexionsParRole'][$role] = 0;
            }
            $stats['connexionsParRole'][$role]++;
        }
        
        return $this->render('utilisateurs/historique_connexion.html.twig', [
            'historique' => $loginHistory,
            'stats' => $stats
        ]);
    }

    #[Route('/admin/user/{id}/historique', name: 'admin_user_login_history')]
    public function userLoginHistory(int $id, LoginHistoryLogger $loginLogger, EntityManagerInterface $entityManager): Response
    {
        // Ajouter du débogage
        error_log("Accès à l'historique de l'utilisateur ID: $id");
        
        try {
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
            
            if (!$utilisateur) {
                throw $this->createNotFoundException('Utilisateur non trouvé');
            }
            
            error_log("Utilisateur trouvé: " . $utilisateur->getUsername());
            
            $loginHistory = $loginLogger->getUserLoginHistory($id);
            error_log("Historique récupéré: " . count($loginHistory) . " entrées");
            
            // Rendre le template avec try/catch pour capturer les erreurs
            try {
                return $this->render('utilisateurs/historique_utilisateur.html.twig', [
                    'utilisateur' => $utilisateur,
                    'historique' => $loginHistory
                ]);
            } catch (\Exception $e) {
                error_log("ERREUR TEMPLATE: " . $e->getMessage());
                
                // Afficher une page d'erreur explicite au lieu d'une page blanche
                return new Response(
                    '<html><body><h1>Erreur d\'affichage</h1><p>' . $e->getMessage() . '</p>' .
                    '<a href="' . $this->generateUrl('admin_dashboard') . '">Retour au tableau de bord</a></body></html>'
                );
            }
        } catch (\Exception $e) {
            error_log("ERREUR CONTROLEUR: " . $e->getMessage());
            
            // Afficher une page d'erreur explicite au lieu d'une page blanche
            return new Response(
                '<html><body><h1>Erreur</h1><p>' . $e->getMessage() . '</p>' .
                '<a href="' . $this->generateUrl('admin_dashboard') . '">Retour au tableau de bord</a></body></html>'
            );
        }
    }
}
