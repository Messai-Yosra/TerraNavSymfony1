<?php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private $security;
    
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Affiche le profil de l'utilisateur connecté
     */
    #[Route('/profile', name: 'user_profile')]
    public function index(): Response
    {
        // Vérification si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('utilisateurs/profileClient.html.twig');
    }
    
    /**
     * Met à jour le profil de l'utilisateur
     * 
     * @Route("/profile/update", name="user_profile_update", methods={"POST"})
     */
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw new \Exception('Utilisateur non authentifié ou type incorrect');
        }
        
        try {
            // Récupération des données du formulaire
            $user->setPrenom($request->request->get('prenom'));
            $user->setNom($request->request->get('nom'));
            $user->setEmail($request->request->get('email'));
            
            // Utilisation des bonnes méthodes de l'entité Utilisateur
            $user->setNumTel($request->request->get('numTelephone'));
            $user->setCin($request->request->get('cin'));
            $user->setAddress($request->request->get('adresse'));
            
            // Enregistrement des modifications
            $entityManager->flush();
            
            // Si la requête est en AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès'
                ]);
            }
            
            // Message flash de confirmation
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès!');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
                ]);
            }
            
            $this->addFlash('danger', 'Une erreur est survenue: ' . $e->getMessage());
        }
        
        // Redirection vers la page de profil
        return $this->redirectToRoute('user_profile');
    }
}