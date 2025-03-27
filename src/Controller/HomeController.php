<?php
// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Si l'utilisateur n'est pas connecté, rediriger vers la page de login
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier le rôle de l'utilisateur
        $user = $this->getUser();
        
        // Débogage avancé
        $userClass = get_class($user);
        $availableMethods = get_class_methods($user);
        $email = $user->getEmail();
        
        dump("Classe de l'utilisateur: " . $userClass);
        dump("Email: " . $email);
        dump("Méthodes disponibles: ", $availableMethods);
        
        // Vérifier si getRole existe
        if (method_exists($user, 'getRole')) {
            $role = $user->getRole();
            dump("Rôle direct: " . $role);
            
            // Comparaison avec différentes variantes
            dump("Test admin minuscules: " . ($role === 'admin' ? 'OUI' : 'NON'));
            dump("Test ADMIN majuscules: " . ($role === 'ADMIN' ? 'OUI' : 'NON'));
            dump("Test admin insensible à la casse: " . (strtolower($role) === 'admin' ? 'OUI' : 'NON'));
            
            if ($role === 'admin') {
                // Cette condition devrait être vraie si le rôle est bien 'admin'
                dump("Condition admin vérifiée, redirection vers admin_dashboard");
                return $this->redirectToRoute('admin_dashboard');
            }
            
            // Ajout des cas pour les autres rôles
            if ($role === 'agence') {
                return $this->render('home/homeAgence.html.twig', [
                    'user' => $user,
                ]);
            }
            
            // Si c'est un client ou un autre rôle
            return $this->render('home/homeClient.html.twig', [
                'user' => $user,
            ]);
        } else {
            dump("ERREUR: La méthode getRole n'existe pas!");
        }
        
        // Contournement temporaire: redirection directe pour admin
        if ($email === 'admin@admin.com') {
            dump("Redirection par email pour admin");
            return $this->redirectToRoute('admin_dashboard');
        }
        
        // Si aucune condition précédente n'a déclenché un retour,
        // nous arrivons ici - retourne une vue par défaut pour éviter une erreur
        return $this->render('home/homeClient.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/home', name: 'app_home_client')]
    public function homeClient(): Response
    {
        // Cette route peut être utilisée comme cible après connexion
        return $this->redirectToRoute('app_home');
    }
}