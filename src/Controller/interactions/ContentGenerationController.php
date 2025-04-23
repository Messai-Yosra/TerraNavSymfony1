<?php

namespace App\Controller\interactions;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Service\interactions\ContentGenerationService;
use App\Form\AddPostFormType;

class ContentGenerationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ContentGenerationService $contentGenerationService;

    public function __construct(EntityManagerInterface $entityManager, ContentGenerationService $contentGenerationService)
    {
        $this->entityManager = $entityManager;
        $this->contentGenerationService = $contentGenerationService;
    }

    #[Route('/posts/generate-content', name: 'post_generate_content', methods: ['POST'])]
    public function generateContent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? 'Default prompt for generating content';

        $generatedContent = $this->contentGenerationService->generateContent($prompt);

        if ($generatedContent) {
            return $this->json(['success' => true, 'content' => $generatedContent]);
        }

        return $this->json(['success' => false, 'message' => 'Failed to generate content'], 400);
    }

    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(AddPostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate content using the API
            $generatedContent = $this->contentGenerationService->generateContent($post->getDescription());
            if ($generatedContent) {
                $post->setDescription($generatedContent);
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}