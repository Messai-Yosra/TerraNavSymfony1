<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Entity\Voyage;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VoyageController extends AbstractController
{
    #[Route('/AjoutVoyage', name: 'app_ajout_voyage', methods: ['GET', 'POST'])]
    public function ajoutVoyage(Request $request, OffreRepository $offreRepository, UtilisateurRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            // Récupérer l'utilisateur existant (id=1) au lieu d'en créer un nouveau
            $user = $userRepository->find(1);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_voyage');
            }

            // Création d'un objet Voyage temporaire
            $voyage = new Voyage();
            $voyage->setId_user($user);
            $voyage->setTitre($request->request->get('titre'));
            $voyage->setDestination($request->request->get('destination'));
            $voyage->setPointDepart($request->request->get('pointDepart'));
            $voyage->setDateDepart(new \DateTime($request->request->get('dateDepart')));
            $voyage->setDateRetour(new \DateTime($request->request->get('dateRetour')));
            $voyage->setType($request->request->get('type'));
            $voyage->setNbPlacesD((int)$request->request->get('nbPlacesD'));
            $voyage->setPrix((float)$request->request->get('prix'));
            $voyage->setDescription($request->request->get('description'));

            // Gestion des images uploadées
            $uploadedFiles = $request->files->get('images');
            $imagePaths = [];

            if ($uploadedFiles) {
                $projectDir = $this->getParameter('kernel.project_dir');

                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                        $relativePath = 'img/voyages/'.$newFilename;
                        $absolutePath = $projectDir.'/public/'.$relativePath;

                        // Déplacer le fichier
                        $uploadedFile->move(
                            $projectDir.'/public/img/voyages/',
                            $newFilename
                        );

                        // Stocker le chemin absolu Windows
                        $windowsPath = str_replace('/', '\\', $absolutePath);
                        $imagePaths[] = $windowsPath;
                    }
                }
            }

            if (empty($imagePaths)) {
                $defaultPath = str_replace(
                    '/',
                    '\\',
                    $this->getParameter('kernel.project_dir').'/public/img/about-1.jpg'
                );
                $imagePaths[] = $defaultPath;
            }

            $voyage->setPathImages(implode('***', $imagePaths));
            // Gestion de l'offre
            $idOffre = $request->request->get('id_offre');
            if ($idOffre) {
                $offre = $offreRepository->find($idOffre);
                $voyage->setId_offre($offre);
            }

            // Stockage temporaire en session
            $session = $request->getSession();
            $session->set('voyage_temporaire', $voyage);

            return $this->redirectToRoute('app_confirmation_ajout');
        }

        // Pour la méthode GET, afficher le formulaire
        $offres = $offreRepository->findAll();
        return $this->render('voyages/AjouterVoyage.html.twig', [
            'offres' => $offres
        ]);
    }

    #[Route('/confirmationAjout', name: 'app_confirmation_ajout')]
    public function confirmationAjout(Request $request): Response
    {
        $session = $request->getSession();
        $voyage = $session->get('voyage_temporaire');

        if (!$voyage) {
            $this->addFlash('error', 'Aucun voyage à confirmer');
            return $this->redirectToRoute('app_ajout_voyage');
        }

        // Séparer les images pour l'affichage
        $images = explode('***', $voyage->getPathImages());
        $voyage->setImageList($images);

        return $this->render('voyages/ConfirmerAjoutVoyage.html.twig', [
            'voyage' => $voyage
        ]);
    }

    #[Route('/publierVoyage', name: 'app_publier_voyage', methods: ['POST'])]
    public function publierVoyage(Request $request, EntityManagerInterface $entityManager, OffreRepository $offreRepository, UtilisateurRepository $userRepository): Response
    {
        $session = $request->getSession();
        $voyageData = $session->get('voyage_temporaire');

        if (!$voyageData) {
            $this->addFlash('error', 'Aucun voyage à publier');
            return $this->redirectToRoute('app_ajout_voyage');
        }

        try {
            // Récupérer l'utilisateur existant (id=1)
            $user = $userRepository->find(1);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_voyage');
            }

            // Créez une nouvelle instance de Voyage
            $voyage = new Voyage();
            $voyage->setId_user($user);
            $voyage->setTitre($voyageData->getTitre());
            $voyage->setDestination($voyageData->getDestination());
            $voyage->setPointDepart($voyageData->getPointDepart());
            $voyage->setDateDepart($voyageData->getDateDepart());
            $voyage->setDateRetour($voyageData->getDateRetour());
            $voyage->setType($voyageData->getType());
            $voyage->setNbPlacesD($voyageData->getNbPlacesD());
            $voyage->setPrix($voyageData->getPrix());
            $voyage->setDescription($voyageData->getDescription());
            $voyage->setPathImages($voyageData->getPathImages());

            // Gestion de l'offre
            if ($voyageData->getId_offre()) {
                // Récupère l'offre depuis la base de données
                $offre = $offreRepository->find($voyageData->getId_offre()->getId());

                if ($offre) {
                    $voyage->setId_offre($offre);
                } else {
                    $this->addFlash('warning', 'L\'offre associée n\'existe pas');
                }
            }

            $entityManager->persist($voyage);
            $entityManager->flush();

            $session->remove('voyage_temporaire');

            $this->addFlash('success', 'Le voyage a été publié avec succès !');
            return $this->redirectToRoute('app_voyages_agence');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : '.$e->getMessage());
            return $this->redirectToRoute('app_confirmation_ajout');
        }
    }




}