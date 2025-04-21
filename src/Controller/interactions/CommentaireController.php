<?php
namespace App\Controller\interactions;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\CommentaireType; 
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
class CommentaireController extends AbstractController
{
    #[Route('/commentaire/new/{postId}', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(int $postId, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le post associé à l'ID
        $post = $entityManager->getRepository(Post::class)->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas');
        }

        // Récupérer l'utilisateur avec l'ID 1 (s'il existe)
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur avec l\'ID 1 non trouvé');
        }

        $commentaire = new Commentaire();

        // Assigner l'utilisateur avec l'ID 1 au commentaire
        $commentaire->setId_user($user);

        // Créer un formulaire pour ajouter un commentaire (tu peux ajouter un formulaire symfony si nécessaire)
        $form = $this->createFormBuilder($commentaire)
            ->add('contenu', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigner le post à ce commentaire
            $commentaire->setId_post($post);
            // Assigner la date du commentaire
            $commentaire->setDate(new \DateTime());
            $post->setNbCommentaires($post->getNbCommentaires() + 1);

            // Sauvegarder dans la base de données
            $entityManager->persist($commentaire);
            $entityManager->flush();

            // Rediriger ou afficher un message de succès
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
    
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
