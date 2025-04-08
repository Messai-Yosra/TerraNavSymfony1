<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Form\voyages\OffreType;
use App\Repository\Utilisateur\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreController extends AbstractController
{
    #[Route('/AjoutOffre', name: 'app_ajout_offre', methods: ['GET', 'POST'])]
    public function ajoutOffre(Request $request, UtilisateurRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            // Récupérer l'utilisateur existant (id=1)
            $user = $userRepository->find(1);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_offre');
            }

            // Création d'un objet Offre temporaire
            $offre = new Offre();
            $offre->setId_user($user);
            $offre->setTitre($request->request->get('titre'));
            $offre->setReduction((float)$request->request->get('reduction'));
            $offre->setDateDebut(new \DateTime($request->request->get('dateDebut')));
            $offre->setDateFin(new \DateTime($request->request->get('dateFin')));
            $offre->setDescription($request->request->get('description'));

            // Gestion de l'image uploadée
            $uploadedFile = $request->files->get('image');
            $imagePath = null;

            if ($uploadedFile) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                $relativePath = 'img/offres/'.$newFilename;
                $absolutePath = $projectDir.'/public/'.$relativePath;

                // Déplacer le fichier
                $uploadedFile->move(
                    $projectDir.'/public/img/offres/',
                    $newFilename
                );

                // Stocker le chemin absolu Windows
                $windowsPath = str_replace('/', '\\', $absolutePath);
                $imagePath = $windowsPath;
            }

            if (!$imagePath) {
                $defaultPath = str_replace(
                    '/',
                    '\\',
                    $this->getParameter('kernel.project_dir').'/public/img/about-1.jpg'
                );
                $imagePath = $defaultPath;
            }

            $offre->setImagePath($imagePath);

            // Stockage temporaire en session
            $session = $request->getSession();
            $session->set('offre_temporaire', $offre);

            return $this->redirectToRoute('app_confirmation_ajout_offre');
        }

        // Pour la méthode GET, afficher le formulaire
        return $this->render('voyages/AjouterOffre.html.twig');
    }

    #[Route('/confirmationAjoutOffre', name: 'app_confirmation_ajout_offre')]
    public function confirmationAjoutOffre(Request $request): Response
    {
        $session = $request->getSession();
        $offre = $session->get('offre_temporaire');

        if (!$offre) {
            $this->addFlash('error', 'Aucune offre à confirmer');
            return $this->redirectToRoute('app_ajout_offre');
        }

        return $this->render('voyages/ConfirmerAjoutOffre.html.twig', [
            'offre' => $offre
        ]);
    }

    #[Route('/publierOffre', name: 'app_publier_offre', methods: ['POST'])]
    public function publierOffre(Request $request, EntityManagerInterface $entityManager, UtilisateurRepository $userRepository): Response
    {
        $session = $request->getSession();
        $offreData = $session->get('offre_temporaire');

        if (!$offreData) {
            $this->addFlash('error', 'Aucune offre à publier');
            return $this->redirectToRoute('app_ajout_offre');
        }

        try {
            // Récupérer l'utilisateur existant (id=1)
            $user = $userRepository->find(1);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_offre');
            }

            // Créez une nouvelle instance de Offre
            $offre = new Offre();
            $offre->setId_user($user);
            $offre->setTitre($offreData->getTitre());
            $offre->setReduction($offreData->getReduction());
            $offre->setDateDebut($offreData->getDateDebut());
            $offre->setDateFin($offreData->getDateFin());
            $offre->setDescription($offreData->getDescription());
            $offre->setImagePath($offreData->getImagePath());

            $entityManager->persist($offre);
            $entityManager->flush();

            $session->remove('offre_temporaire');

            $this->addFlash('success', 'L\'offre a été publiée avec succès !');
            return $this->redirectToRoute('app_offres_agence');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : '.$e->getMessage());
            return $this->redirectToRoute('app_confirmation_ajout_offre');
        }
    }

    public function new(Request $request): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        return $this->redirectToRoute('app_confirmation_ajout_offre');
    }
}