<?php

namespace App\Controller\utilisateurs;
use App\Entity\Panier; //create panier for user
use App\Entity\Utilisateur;
use App\Form\SignUpType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityController extends AbstractController
{
    #[Route('/security', name: 'app_security')]
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($request->query->get('banned')) {
            $this->addFlash('danger', 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.');
        }

        // Récupérer l'erreur d'authentification s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur entré
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    // Add this route
    #[Route('/login_check', name: 'app_login_check')]
    public function loginCheck(): Response
    {
        // This route will be handled by Symfony's security system
        throw new \LogicException('This method should never be reached!');
    }
    #[Route('/signup', name: 'app_signup')]
    public function signup(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(SignUpType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            );
            $user->setPassword($hashedPassword);
            
            // Save the user to the database
            $entityManager->persist($user);
            $entityManager->flush();

            //create panier for user
            $panier = new Panier();
            $panier->setIdUser($user);  // Pass the User OBJECT, not ID
            $panier->setPrixTotal(0.0);
            $entityManager->persist($panier);
            $entityManager->flush();

            // Add a flash message
            $this->addFlash('success', 'Votre compte a été créé avec succès!');

            // Redirect to login page after successful registration
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}
