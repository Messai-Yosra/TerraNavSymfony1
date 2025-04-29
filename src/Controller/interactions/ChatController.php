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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Story; 
use App\Service\interactions\PostDescriptionGenerator;
use App\Service\interactions\ProfanityFilter; 
use App\Form\StoryType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ChatController extends AbstractController
{
    private $entityManager;
    private $contentGenerationService;
    private $profanityFilter;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProfanityFilter $profanityFilter
    ) {
        $this->entityManager = $entityManager;
        $this->profanityFilter = $profanityFilter;
    }

    #[Route('/new', name: 'app_post_new')]
    public function addPost(Request $request): Response
    {
        $post = new Post(); 
        $form = $this->createForm(AddPostFormType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer et filtrer la description
            $description = $form->get('description')->getData();
            
            // Vérifier si la description est vide
            if (empty(trim($description))) {
                $this->addFlash('error', 'La description ne peut pas être vide.');
                return $this->render('interactions/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Filtrer le contenu inapproprié
            $filteredDescription = $this->profanityFilter->filter($description);
            
            // Vérifier si le contenu a été modifié par le filtre
            if ($filteredDescription !== $description) {
                $this->addFlash('warning', 'Votre description contenait du contenu inapproprié qui a été filtré.');
            }

            // Mettre à jour la description filtrée
            $post->setDescription($filteredDescription);

            // Gestion de l'image
            $image = $form->get('image')->getData();
            if ($image) {
                $filename = uniqid() . '.' . $image->guessExtension();
                $image->move(
                    $this->getParameter('uploads_directory'),
                    $filename
                );
                $post->setImage($filename);
            }
    
            $user = $this->getUser(); 
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé.');
            }
            $post->setId_user($user);
    
            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre post a été créé avec succès.');
            return $this->redirectToRoute('app_chat');
        }
    
        return $this->render('interactions/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    


    #[Route('/ChatClient', name: 'app_chat')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get page number from request
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Number of posts per page

        // Get post repository
        $postRepository = $entityManager->getRepository(Post::class);
        
        // Get total number of posts
        $totalPosts = $postRepository->count(['statut' => 'traitée']);
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get paginated posts
        $posts = $postRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'traitée')
            ->orderBy('p.date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Calculate total pages
        $totalPages = ceil($totalPosts / $limit);

        // Get active stories
        $storyRepository = $entityManager->getRepository(Story::class);
        $activeStories = $storyRepository->findActiveStories();

        return $this->render('interactions/chatClient.html.twig', [
            'posts' => $posts,
            'activeStories' => $activeStories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit
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
// src/Controller/PostController.php


#[Route('/post/generate-description', name: 'post_generate_description', methods: ['POST'])]
public function generateDescription(
    Request $request,
    PostDescriptionGenerator $descriptionGenerator
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $context = $data['context'] ?? '';

    try {
        $description = $descriptionGenerator->generatePostDescription(
            $context,
            $data['type'] ?? 'general',
            $data['style'] ?? 'convivial',
            true
        );

        return $this->json([
            'success' => true,
            'content' => $description
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
#[Route('/story/new', name: 'app_story_new')]
public function addStory(
    Request $request, 
    ValidatorInterface $validator,
    SluggerInterface $slugger
): Response
{
    // Vérification de l'utilisateur
    $user = $this->getUser();
    if (!$user) {
        $this->addFlash('error', 'Vous devez être connecté pour ajouter une story.');
        return $this->redirectToRoute('app_login');
    }

    $story = new Story();
    $form = $this->createForm(StoryType::class, $story);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            /** @var UploadedFile $mediaFile */
            $mediaFile = $form->get('media')->getData();
            
            // Vérification du fichier média
            if (!$mediaFile) {
                throw new \Exception('Veuillez sélectionner un média (image ou vidéo).');
            }

            // Validation du type de fichier
            $mimeType = $mediaFile->getMimeType();
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
            if (!in_array($mimeType, $allowedTypes)) {
                throw new \Exception('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou MP4.');
            }

            // Création d'un nom de fichier unique
            $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$mediaFile->guessExtension();

            // Déplacement du fichier
            try {
                $mediaFile->move(
                    $this->getParameter('stories_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                throw new \Exception('Une erreur est survenue lors du téléchargement du fichier.');
            }

            // Configuration de la story
            $story->setMedia($newFilename);
            $story->setIdUser($user);
            $story->setCreatedAt(new \DateTime());
            $story->setIsActive(true);

            // Validation de l'entité
            $errors = $validator->validate($story);
            if (count($errors) > 0) {
                throw new \Exception($errors[0]->getMessage());
            }

            // Persistence des données
            $this->entityManager->persist($story);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre story a été publiée avec succès!');
            return $this->redirectToRoute('app_chat');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('interactions/newStory.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }

    return $this->render('interactions/newStory.html.twig', [
        'form' => $form->createView(),
    ]);
}
}