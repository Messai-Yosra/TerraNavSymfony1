<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        
        if (!$user instanceof Utilisateur) {
            // Fallback en cas de problème
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }
        
        // Vérification du rôle (selon la structure de votre entité Utilisateur)
        if ($user->getRole() === 'admin') {
            // Redirection vers le dashboard admin
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } else {
            // Redirection vers la page profil pour les clients
            return new RedirectResponse($this->urlGenerator->generate('user_profile'));
        }
    }
}