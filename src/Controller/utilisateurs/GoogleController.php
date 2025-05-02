<?php
// src/Controller/GoogleController.php
namespace App\Controller\utilisateurs;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Repository\Reservation\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Rediriger vers Google
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile' // Les scopes demandés
            ], []);  // Additional parameters
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, EntityManagerInterface $entityManager, PanierRepository $panierRepository): RedirectResponse
    {
        // L'authentification est gérée par le système de sécurité de Symfony
        // Cette méthode est appelée après une authentification réussie
        
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        
        if ($user instanceof Utilisateur) {
            // Vérifier si l'utilisateur a déjà un panier
            $panier = $panierRepository->findByUser($user->getId());
            
            // Si l'utilisateur n'a pas de panier, créer un nouveau panier
            if (!$panier) {
                $panier = new Panier();
                $panier->setIdUser($user);
                $panier->setPrixTotal(0.0);
                $entityManager->persist($panier);
                $entityManager->flush();
            }
        }
        
        return $this->redirectToRoute('app_home');
    }
}