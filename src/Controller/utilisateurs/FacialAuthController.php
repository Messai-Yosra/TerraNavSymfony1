<?php

namespace App\Controller\utilisateurs;

use App\Entity\Utilisateur;
use App\Form\SignUpType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/facial-auth')]
class FacialAuthController extends AbstractController
{
    private $client;
    private $entityManager;
    private $tokenStorage;
    private $eventDispatcher;
    private $urlGenerator;
    
    public function __construct(
        HttpClientInterface $client, 
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->urlGenerator = $urlGenerator;
    }

    #[Route('/login', name: 'app_facial_auth_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/facial_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/enroll', name: 'app_facial_auth_enroll')]
    public function enroll(): Response
    {
        // Make sure the user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Get authenticated user
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/facial_enroll.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/verify-face', name: 'app_facial_auth_verify', methods: ['POST'])]
    public function verifyFace(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['face_image'])) {
            return new JsonResponse(['success' => false, 'message' => 'No face image provided'], Response::HTTP_BAD_REQUEST);
        }
        
        // Extract username if provided for login
        $username = $data['username'] ?? null;
        $userId = null;
        
        // If username provided, get user's ID
        if ($username) {
            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $username]);
            
            if (!$user) {
                $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['username' => $username]);
            }
            
            if ($user) {
                $userId = $user->getId();
            }
        }
        
        try {
            // Call the facial recognition service
            $response = $this->client->request('POST', 'http://localhost:5001/verify', [
                'json' => [
                    'face_image' => $data['face_image'],
                    'user_id' => $userId
                ]
            ]);
            
            $result = json_decode($response->getContent(), true);
            
            // If verification succeeded, authenticate the user
            if ($result['success']) {
                // Find the user by ID
                $user = $this->entityManager->getRepository(Utilisateur::class)->find($result['user_id']);
                
                if (!$user) {
                    return new JsonResponse(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
                }
                
                // Check if user is banned
                if (method_exists($user, 'isBanned') && $user->isBanned()) {
                    return new JsonResponse([
                        'success' => false, 
                        'message' => 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.'
                    ], Response::HTTP_FORBIDDEN);
                }
                
                // Create the token
                $token = new UsernamePasswordToken(
                    $user,
                    'main', // Firewall name
                    $user->getRoles()
                );
                
                // Set token in security context
                $this->tokenStorage->setToken($token);
                
                // Dispatch login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->eventDispatcher->dispatch($event);
                
                // Redirection conditionnelle selon le rôle de l'utilisateur
                $redirectUrl = $this->urlGenerator->generate('user_profile'); // Redirection par défaut
                
                // Si l'utilisateur est un admin, le rediriger vers le dashboard admin
                if (in_array('ROLE_ADMIN', $user->getRoles())) {
                    $redirectUrl = $this->urlGenerator->generate('admin_dashboard');
                }
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Authentication successful',
                    'redirect' => $redirectUrl
                ]);
            }
            
            // If verification failed
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication failed: ' . ($result['message'] ?? 'Unknown error')
            ], Response::HTTP_UNAUTHORIZED);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication service error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/enroll-face', name: 'app_facial_auth_save', methods: ['POST'])]
    public function enrollFace(Request $request): JsonResponse
    {
        // Make sure the user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Get authenticated user
        /**
         * @var Utilisateur $user
         */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['face_image'])) {
            return new JsonResponse(['success' => false, 'message' => 'No face image provided'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            // Call the facial recognition service
            $response = $this->client->request('POST', 'http://localhost:5001/enroll', [
                'json' => [
                    'user_id' => $user->getId(),
                    'user_email' => $user->getEmail(),
                    'face_image' => $data['face_image']
                ]
            ]);
            
            $result = json_decode($response->getContent(), true);
            
            // Add the hasFacialAuth flag to the user
            if ($result['success']) {
                $user->setHasFacialAuth(true);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
            
            return new JsonResponse($result);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error enrolling face: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove-face', name: 'app_facial_auth_remove', methods: ['POST'])]
    public function removeFace(Request $request): JsonResponse
    {
        // Make sure the user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Get authenticated user
        /**
         * @var Utilisateur $user
         */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        try {
            // Call the facial recognition service
            $response = $this->client->request('DELETE', 'http://localhost:5001/remove/' . $user->getId());
            
            $result = json_decode($response->getContent(), true);
            
            // Update the hasFacialAuth flag
            if ($result['success']) {
                $user->setHasFacialAuth(false);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
            
            return new JsonResponse($result);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error removing face data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}