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
    
            $user = $this->entityManager->getRepository(Utilisateur::class)->find(1);
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur avec ID 1 non trouvé.');
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
#[Route('/post/{id}/like', name: 'app_post_like', methods: ['POST'])]
public function likePost(Post $post): JsonResponse
{
    // Example logic for liking a post
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 403);
    }

    // Toggle like logic (e.g., add/remove like)
    $liked = false; // Replace with actual logic to check if the user already liked the post
    if ($liked) {
        // Remove like
        $post->removeLike($user);
    } else {
        // Add like
        $post->addLike($user);
    }

    // Save changes
    $this->getDoctrine()->getManager()->flush();

    return new JsonResponse([
        'success' => true,
        'newLikeCount' => $post->getLikes()->count(), // Replace with actual like count logic
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
}