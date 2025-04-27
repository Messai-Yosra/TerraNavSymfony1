<?php
namespace App\Controller\interactions;

use App\Entity\Commentaire;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\CommentaireType; 
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommentaireController extends AbstractController
{private $mailer;
    private $urlGenerator;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }
    
    #[Route('/commentaire/new/{postId}', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(int $postId, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le post associé à l'ID
        $post = $entityManager->getRepository(Post::class)->find($postId);
    
        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas');
        }
    
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non connecté');
        }
    
        $commentaire = new Commentaire();
        $commentaire->setId_user($user);
    
        // Créer le formulaire
        $form = $this->createFormBuilder($commentaire)
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'placeholder' => 'Écrivez votre commentaire ici...',
                    'rows' => 5,
                    'class' => 'form-control'
                ]
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Configurer le commentaire
            $commentaire->setId_post($post);
            $commentaire->setDate(new \DateTime());
            
            // Mettre à jour le compteur de commentaires
            $post->setNbCommentaires($post->getNbCommentaires() + 1);
    
            // Enregistrement en base de données
            $entityManager->persist($commentaire);
            $entityManager->flush();
    
            // Envoi de l'email de notification
            $postCreator = $post->getId_user();
            if ($postCreator && $postCreator->getEmail()) {
                $commentLink = $this->urlGenerator->generate('app_chat', [], UrlGeneratorInterface::ABSOLUTE_URL);
                
                // Chemin vers le logo dans le dossier public
                $logoPath = $this->getParameter('kernel.project_dir').'/public/img/TerraNav.png';
                
                if (!file_exists($logoPath)) {
                    throw $this->createNotFoundException('Le logo n\'a pas été trouvé dans public/img/');
                }
    
                // Lire le contenu du logo
                $logoContent = file_get_contents($logoPath);
                $logoMime = mime_content_type($logoPath);
    
                $email = (new Email())
    ->from('terranav4@gmail.com')
    ->to($postCreator->getEmail())
    ->subject('Nouveau commentaire sur votre publication')
    ->embed($logoContent, 'logo', $logoMime)
    ->html($this->renderView('interactions/cmntrmail.html.twig', [
        'postCreator' => $postCreator,
        'user' => $user,
        'post' => $post,
        'commentaire' => $commentaire,
        'commentLink' => $commentLink
    ]));
    
                $this->mailer->send($email);
            }
    
            // Message flash et redirection
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès!');
            return $this->redirectToRoute('app_chat');
        }
    
        return $this->render('interactions/newCommentaire.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }
    #[Route('/post/{id}', name: 'app_post_show')]
    public function show(int $id): Response
    {
        $post = $this->entityManager->getRepository(Post::class)->find($id);
    
        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas');
        }
    
        return $this->render('interactions/chatClient.html.twig', [
            'post' => $post,
        ]);
    }
    

    #[Route('/comment/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
    public function editComment(int $id, Request $request): Response
    {
        $commentaire = $this->entityManager->getRepository(Commentaire::class)->find($id);
        
        if (!$commentaire) {
            throw $this->createNotFoundException('Le commentaire n\'existe pas.');
        }
    
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde des modifications
            $this->entityManager->flush();
    
            // Redirection vers la page des détails du post
            return $this->redirectToRoute('app_chat');
        }
    
        return $this->render('interactions/editCommentaire.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,  
        ]);
    }
    

    #[Route('/commentaire/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
    public function deleteComment(int $id, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le commentaire par son ID
        $commentaire = $entityManager->getRepository(Commentaire::class)->find($id);
    
        if (!$commentaire) {
            throw $this->createNotFoundException('Le commentaire n\'existe pas.');
        }
    
        $post = $commentaire->getId_Post();
    
        if (!$post) {
            throw $this->createNotFoundException('Le post associé n\'existe pas.');
        }
    
        // Décrémenter le compteur de commentaires du post
        $post->setNbCommentaires($post->getNbCommentaires() - 1);
    
        // Persister les changements du post
        $entityManager->persist($post);
    
        // Supprimer le commentaire de la base de données
        $entityManager->remove($commentaire);
        $entityManager->flush();
    
        // Rediriger vers la page des posts (ou une autre page)
        return $this->redirectToRoute('app_chat');
    }
    

}


?>
