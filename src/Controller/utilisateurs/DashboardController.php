<?php

namespace App\Controller\utilisateurs;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\UtilisateurType;

final class DashboardController extends AbstractController
{
    #[Route('/DashboardClient', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les utilisateurs de la base de données
        $utilisateurs = $entityManager->getRepository(Utilisateur::class)->findAll();
        
        return $this->render('utilisateurs/dashboardAdmin.html.twig', [
            'utilisateurs' => $utilisateurs
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
}
