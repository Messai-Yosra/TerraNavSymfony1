<?php
// src/Controller/ResetPasswordController.php
namespace App\Controller\utilisateurs;

use App\Entity\Utilisateur;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Afficher et traiter le formulaire de demande de réinitialisation
     */
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $tokenGenerator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Page de confirmation après envoi d'email
     */
    #[Route('/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    /**
     * Validation du token et affichage du formulaire de réinitialisation
     */
    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, string $token): Response
    {
        // Chercher l'utilisateur par token
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
            'reset_token' => $token
        ]);

        // Si aucun utilisateur trouvé ou token expiré
        if (!$user || $user->getResetTokenExpiry() < new \DateTime()) {
            $this->addFlash('danger', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Formulaire de réinitialisation
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Réinitialiser le token
            $user->setResetToken(null);
            $user->setResetTokenExpiry(null);

            // Encoder le nouveau mot de passe
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($encodedPassword);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * Envoyer un email avec le lien de réinitialisation
     */
    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator): Response
    {
        try {
            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
                'email' => $emailFormData,
            ]);

            // Ajouter cette ligne pour déboguer
            if ($user) {
                $this->logger->info('Utilisateur trouvé: ' . $user->getEmail());
            } else {
                $this->logger->warning('Aucun utilisateur trouvé avec cet email: ' . $emailFormData);
                return $this->redirectToRoute('app_check_email');
            }

            // Générer un token
            $token = $tokenGenerator->generateToken();
            $user->setResetToken($token);
            $user->setResetTokenExpiry(new \DateTime('+1 hour'));
            $this->entityManager->flush();

            // Générer l'URL
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            
            // Préparer et envoyer l'email
            $email = (new TemplatedEmail())
                ->from(new Address('troudi111salim@gmail.com', 'TerraNav Password Reset'))
                ->to($user->getEmail())
    
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('reset_password/email.html.twig') // Chemin correct
                ->context([
                    'resetUrl' => $resetUrl,
                    'user' => $user,
                    'expiry_date' => $user->getResetTokenExpiry()->format('d/m/Y H:i')
                ]);

            $mailer->send($email);
            $this->logger->info('Email envoyé à: ' . $user->getEmail());

            return $this->redirectToRoute('app_check_email');
        } catch (\Exception $e) {
            // Ajouter ce bloc try/catch pour capturer les erreurs
            $this->logger->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de l\'email: ' . $e->getMessage());
            return $this->redirectToRoute('app_forgot_password_request');
        }
    }
}