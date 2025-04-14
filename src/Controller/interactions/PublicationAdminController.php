<?php

namespace App\Controller\interactions;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;

final class PublicationAdminController extends AbstractController
{
    // Route pour afficher tous les posts
    #[Route('/PublicationsAdmin', name: 'admin_publications')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupérer tous les posts via l'EntityManager
        $posts = $em->getRepository(Post::class)->findAll();
        
        return $this->render('interactions/publicationAdmin.html.twig', [
            'posts' => $posts,
        ]);
    }

    // Route pour traiter un post
    #[Route('/PublicationsAdmin/post/{id}/traiter', name: 'admin_post_traiter')]
    public function traiter(int $id, EntityManagerInterface $em): Response
    {
        // Recherche du post à traiter
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException("Post introuvable.");
        }

        // Mise à jour du statut du post
        $post->setStatut('traitée');
        $em->flush(); // Sauvegarde de la mise à jour

        // Retour à la liste des publications
        return $this->redirectToRoute('admin_publications');
    }

    // Route pour supprimer un post
    #[Route('/PublicationsAdmin/post/{id}/delete', name: 'admin_post_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        // Recherche du post à supprimer
        $post = $em->getRepository(Post::class)->find($id);
        if ($post) {
            $em->remove($post); // Suppression du post
            $em->flush(); // Sauvegarde de la suppression
        }

        // Retour à la liste des publications
        return $this->redirectToRoute('admin_publications');
    }

    // Route pour afficher les détails d'un post
    #[Route('/PublicationsAdmin/post/{id}/details', name: 'admin_post_details')]
    public function postDetails(int $id, EntityManagerInterface $em): Response
    {
        // Récupérer le post et ses commentaires
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException('Le post n\'existe pas.');
        }

        // Affichage des détails du post
        return $this->render('interactions/showPostDetails.html.twig', [
            'post' => $post,
        ]);
    }
}
