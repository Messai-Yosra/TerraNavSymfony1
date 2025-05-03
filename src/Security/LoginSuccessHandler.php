<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $urlGenerator;
    private $tokenStorage;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            // Fallback en cas de problème
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        // NOUVEAU: Vérification du bannissement (version robuste)
        try {
            $isBanned = method_exists($user, 'isBanned')
                ? $user->isBanned()
                : ($user->getBanned() !== null);

            if ($isBanned) {
                // Déconnexion
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();

                // Rediriger avec un paramètre d'erreur
                return new RedirectResponse(
                    $this->urlGenerator->generate('app_login', ['banned' => 1])
                );
            }
        } catch (\Exception $e) {
            // Log l'erreur mais continuer le processus
            error_log('Erreur lors de la vérification du bannissement: ' . $e->getMessage());
        }

        // Vérification du rôle (selon la structure de votre entité Utilisateur)
        if ($user->getRole() === 'admin') {
            // Redirection vers le dashboard admin
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }if ($user->getRole() === 'AGENCE' & $user->getTypeAgence() === 'VOYAGE' ) {
        // Redirection vers le dashboard admin
        return new RedirectResponse($this->urlGenerator->generate('app_voyages_agence'));
    }
        if ($user->getRole() === 'AGENCE' & $user->getTypeAgence() === 'TRANSPORT' ) {
            // Redirection vers le dashboard admin
            return new RedirectResponse($this->urlGenerator->generate('client_transports_list'));
        }
    else {
        // Redirection vers la page profil pour les clients
        return new RedirectResponse($this->urlGenerator->generate('user_profile'));
    }
    }
}