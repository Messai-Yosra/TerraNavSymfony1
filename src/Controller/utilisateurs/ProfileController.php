<?php

namespace App\Controller\utilisateurs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private $security;
    private $entityManager;
    
    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * Affiche le profil de l'utilisateur connecté
     */
    #[Route('/profile', name: 'user_profile')]
    public function index(): Response
    {
        // Vérification si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('utilisateurs/profileClient.html.twig');
    }
    
    
    #[Route('/profile/update', name: 'user_profile_update')]
    public function updateProfile(Request $request): Response
    {
        // Vérification si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw new \Exception('Utilisateur non authentifié ou type incorrect');
        }
        
        try {
            // Récupérer les données du formulaire
            $prenom = $request->request->get('prenom');
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $numTelephone = $request->request->get('numTelephone');
            $cin = $request->request->get('cin');
            $adresse = $request->request->get('adresse');
            
            // Mise à jour des données de l'utilisateur
            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setNumTel($numTelephone);
            $user->setCin($cin);
            $user->setAddress($adresse);
            
            // Vérifier s'il y a une photo de la caméra ou téléchargée
            $photoCamera = $request->files->get('photo_camera');
            $photoUpload = $request->files->get('photo_upload');
            
            $photo = $photoCamera ?: $photoUpload;
            
            if ($photo) {
                // Vérification du type de fichier
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($photo->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('danger', 'Type de fichier non autorisé. Veuillez télécharger une image (JPG, PNG, GIF).');
                    return $this->redirectToRoute('user_profile');
                }
                
                // Vérification de la taille du fichier (2 Mo max)
                if ($photo->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash('danger', 'L\'image ne doit pas dépasser 2 Mo.');
                    return $this->redirectToRoute('user_profile');
                }
                
                // Générer un nom de fichier unique
                $newFilename = 'profile-' . $user->getId() . '-' . uniqid() . '.' . $photo->guessExtension();
                
                try {
                    // Répertoire de destination
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Déplacer le fichier
                    $photo->move($uploadDir, $newFilename);
                    
                    // Supprimer l'ancienne photo si elle existe
                    if ($user->getPhoto() && file_exists($uploadDir . '/' . $user->getPhoto())) {
                        unlink($uploadDir . '/' . $user->getPhoto());
                    }
                    
                    // Mettre à jour l'utilisateur avec le nouveau nom de fichier
                    $user->setPhoto($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du téléchargement de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('user_profile');
                }
            }
            
            // Traitement de l'image base64
            $base64Image = $request->request->get('base64_camera_image');
            if ($base64Image && !empty($base64Image)) {
                try {
                    // Extraire les données d'image
                    list($type, $data) = explode(';', $base64Image);
                    list(, $data) = explode(',', $data);
                    $imageData = base64_decode($data);
                    
                    // Créer un nom de fichier unique
                    $newFilename = 'profile-' . $user->getId() . '-' . uniqid() . '.jpg';
                    
                    // Enregistrer l'image
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile';
                    
                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    file_put_contents("$uploadDir/$newFilename", $imageData);
                    
                    // Supprimer l'ancienne photo si elle existe
                    if ($user->getPhoto() && file_exists($uploadDir . '/' . $user->getPhoto())) {
                        unlink($uploadDir . '/' . $user->getPhoto());
                    }
                    
                    // Mettre à jour l'utilisateur avec le nouveau nom de fichier
                    $user->setPhoto($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du traitement de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('user_profile');
                }
            }
            
            // Enregistrement des modifications
            $this->entityManager->flush();
            
            // Si la requête est en AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès'
                ]);
            }
            
            // Message flash de confirmation
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès!');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
                ]);
            }
            
            $this->addFlash('danger', 'Une erreur est survenue: ' . $e->getMessage());
        }
        
        // Redirection vers la page de profil
        return $this->redirectToRoute('user_profile');
    }

    // Ajoutez cette méthode utilitaire pour sécuriser les noms de fichiers
    private function slugify($string): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }
}

