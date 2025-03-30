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
    
    public function __construct(Security $security)
    {
        $this->security = $security;
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
    
    
    #[Route('/profile/update', name: 'user_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw new \Exception('Utilisateur non authentifié ou type incorrect');
        }
        
        try {
            // Récupération des données du formulaire
            $user->setPrenom($request->request->get('prenom'));
            $user->setNom($request->request->get('nom'));
            $email = $request->request->get('email');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email invalide');
            }
            $user->setEmail($email);
            
            // Utilisation des bonnes méthodes de l'entité Utilisateur
            $user->setNumTel($request->request->get('numTelephone'));
            $user->setCin($request->request->get('cin'));
            $user->setAddress($request->request->get('adresse'));
            
            // Gestion de l'upload de photo
            $photoFile = $request->files->get('photo_upload') ?: 
                         $request->files->get('photo_camera') ?: 
                         $request->files->get('photo');
            if ($photoFile && $photoFile->getSize() > 2000000) { // 2MB
                $this->addFlash('warning', 'La photo est trop volumineuse (max 2Mo)');
                // Ne pas traiter le fichier
                return $this->redirectToRoute('user_profile');
            }
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécurisation du nom de fichier
                $safeFilename = $this->slugify($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();
                
                try {
                    // Déplacement du fichier vers le répertoire où les photos sont stockées
                    $photoFile->move(
                        $this->getParameter('profile_photos_directory'),
                        $newFilename
                    );
                    
                    // Mise à jour de l'URL de la photo dans l'entité
                    $user->setPhoto($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur lors du téléchargement de la photo: ' . $e->getMessage());
                }
            }
            
            // Vérifier s'il y a une photo base64 envoyée depuis la caméra
            $base64Image = $request->request->get('base64_camera_image');
            if ($base64Image) {
                try {
                    // Extraire les données base64
                    if (strpos($base64Image, 'data:image') === 0 && strpos($base64Image, 'base64,') !== false) {
                        list(, $data) = explode('base64,', $base64Image);
                    } else {
                        throw new \Exception('Format d\'image invalide');
                    }
                    $decodedImage = base64_decode($data);
                    
                    // Créer un nom de fichier unique
                    $filename = 'camera-'.uniqid().'.jpg';
                    
                    // Vérifier que le répertoire existe
                    $directory = $this->getParameter('profile_photos_directory');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    } elseif (!is_writable($directory)) {
                        throw new \Exception("Le répertoire $directory n'est pas accessible en écriture");
                    }
                    
                    // Sauvegarder l'image
                    file_put_contents($directory.'/'.$filename, $decodedImage);
                    
                    // Mettre à jour l'utilisateur
                    $user->setPhoto($filename);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur lors du traitement de l\'image prise par la caméra: ' . $e->getMessage());
                }
            }
            
            // Enregistrement des modifications
            $entityManager->flush();
            
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

