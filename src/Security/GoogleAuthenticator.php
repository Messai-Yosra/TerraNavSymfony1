<?php
// src/Security/GoogleAuthenticator.php
namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        // IMPORTANT: La route doit correspondre exactement à celle de votre contrôleur
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        try {
            $accessToken = $this->fetchAccessToken($client);

            return new SelfValidatingPassport(
                new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                    /** @var GoogleUser $googleUser */
                    $googleUser = $client->fetchUserFromToken($accessToken);

                    $email = $googleUser->getEmail();
                    
                    // Journalisation pour déboguer
                    error_log('Google auth: email=' . $email);

                    // Vérifie si l'utilisateur existe déjà
                    $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                        ->findOneBy(['email' => $email]);

                    // Si l'utilisateur existe, retourne-le
                    if ($existingUser) {
                        error_log('Google auth: utilisateur existant trouvé');
                        return $existingUser;
                    }

                    // Sinon, crée un nouvel utilisateur
                    error_log('Google auth: création d\'un nouvel utilisateur');
                    $user = new Utilisateur();
                    $user->setEmail($email);
                    $user->setUsername($googleUser->getName() ?? $email);
                    
                    // Récupérer le nom et prénom depuis Google
                    $fullName = $googleUser->getName() ?? '';
                    $nameParts = explode(' ', $fullName);
                    
                    if (count($nameParts) > 1) {
                        $user->setPrenom($nameParts[0]);
                        $user->setNom(implode(' ', array_slice($nameParts, 1)));
                    } else {
                        $user->setPrenom($fullName);
                        $user->setNom('');
                    }
                    
                    // Générer un mot de passe aléatoire (l'utilisateur ne l'utilisera pas)
                    $user->setPassword(
                        password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT)
                    );
                    
                    // Définir le rôle comme CLIENT (ou autre selon votre logique)
                    $user->setRole('CLIENT');
                    
                    // Traiter l'avatar si disponible
                    if ($googleUser->getAvatar()) {
                        $user->setPhoto($googleUser->getAvatar());
                    }
                    
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    return $user;
                })
            );
        } catch (\Exception $e) {
            error_log('Google auth exception: ' . $e->getMessage());
            throw $e;
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        error_log('Google auth: succès, redirection vers le profil');
        // Redirection après une connexion réussie vers la page de profil
        return new RedirectResponse($this->router->generate('user_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log('Google auth failure: ' . $exception->getMessage());
        
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        
        return new RedirectResponse(
            $this->router->generate('app_login', ['error' => $message])
        );
    }
    
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}