<?php

namespace App\Controller\interactions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Post;
use App\Entity\Utilisateur;
use App\Entity\Reaction;
use App\Form\AddPostFormType;
use App\Repository\PostRepository;
final class ChatController extends AbstractController
{
    private $entityManager;

    // Injecter l'EntityManager dans le contrôleur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/new', name: 'app_post_new')]
    public function addPost(Request $request): Response
    {
        $post = new Post(); 
        $form = $this->createForm(AddPostFormType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $filename = uniqid() . '.' . $image->guessExtension();
                $image->move(
                    $this->getParameter('uploads_directory'),
                    $filename
                );
                $post->setImage($filename);
            }
    
            $user = $this->getUser(); // Récupérer l'utilisateur connecté
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé.');
            }
            $post->setId_user($user);
    
            $this->entityManager->persist($post);
            $this->entityManager->flush();
    
            return $this->redirectToRoute('app_chat');
        }
    
        return $this->render('interactions/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    


    #[Route('/ChatClient', name: 'app_chat')]
public function index(): Response
{
    $posts = $this->entityManager->getRepository(Post::class)->findAll();
    
    dump($posts);

    return $this->render('interactions/chatClient.html.twig', [
        'posts' => $posts, 
    ]);
}

#[Route('/{id}/edit', name: 'app_post_edit')]
public function editPost(int $id, Request $request): Response
{
    $post = $this->entityManager->getRepository(Post::class)->find($id);

    if (!$post) {
        throw $this->createNotFoundException('Le post n\'existe pas.');
    }

    $form = $this->createForm(AddPostFormType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion de l'image
        $image = $form->get('image')->getData();
        if ($image) {
            $filename = uniqid() . '.' . $image->guessExtension();
            $image->move(
                $this->getParameter('uploads_directory'),
                $filename
            );
            $post->setImage($filename); // Met à jour l'image si un fichier est téléchargé
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Le post a été modifié avec succès.');

        return $this->redirectToRoute('app_chat');
    }

    return $this->render('interactions/editPost.html.twig', [
        'form' => $form->createView(),
        'post' => $post, // Transmet la variable 'post' au template
    ]);
}
    #[Route('/post/{id}/delete', name: 'app_post_delete')]
    public function deletePost(int $id): Response
    {
        $post = $this->entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas.');
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_chat');
    }
    #[Route('/post/{id}/details', name: 'app_post_details')]
    public function postDetails(int $id): Response
    {
        // Récupérer le post et ses commentaires
        $post = $this->entityManager->getRepository(Post::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas.');
        }

        return $this->render('interactions/showCommentaire.html.twig', [
            'post' => $post,
        ]);
    }


#[Route('/post/{id}/like', name: 'app_post_like', methods: ['POST'])]
public function toggleLike(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $post = $entityManager->getRepository(Post::class)->find($id);
    
    if (!$post) {
        return $this->json(['success' => false, 'message' => 'Post non trouvé'], 404);
    }
    
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
    }
    
    // Vérifier si l'utilisateur a déjà aimé ce post
    $reactionRepo = $entityManager->getRepository(Reaction::class);
    $existingReaction = $reactionRepo->findByUserAndPost($user, $post);
    
    if ($existingReaction) {
        // Si une réaction existe déjà, on la supprime (unlike)
        $entityManager->remove($existingReaction);
        $liked = false;
        $newCount = $post->getNbReactions() - 1;
    } else {
        // Sinon on ajoute une nouvelle réaction (like)
        $reaction = new Reaction();
        $reaction->setIdUser($user);
        $reaction->setIdPost($post);
        
        $entityManager->persist($reaction);
        $liked = true;
        $newCount = $post->getNbReactions() + 1;
    }
    
    // Mettre à jour le compteur de réactions
    $post->setNbReactions($newCount);
    $entityManager->flush();
    
    return $this->json([
        'success' => true,
        'liked' => $liked,
        'count' => $newCount
    ]);
}
}