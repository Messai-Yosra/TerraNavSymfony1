<?php

namespace App\Controller\voyages;

use App\Entity\Voyage;
use App\Entity\Offre;
use App\Repository\Voyage\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModifierVoyage extends AbstractController
{
    #[Route('/ModifierVoyagebd/{id}', name: 'app_modifier_voyage_bd', methods: ['POST'])]
    public function ModifierVoyage(Request $request, Voyage $voyage, OffreRepository $offreRepository, EntityManagerInterface $entityManager): Response {
        if ($request->isMethod('POST')) {
            try {
                // Mise à jour des propriétés du voyage
                $voyage->setTitre($request->request->get('titre'));
                $voyage->setDestination($request->request->get('destination'));
                $voyage->setPointDepart($request->request->get('pointDepart'));
                $voyage->setDateDepart(new \DateTime($request->request->get('dateDepart')));
                $voyage->setDateRetour(new \DateTime($request->request->get('dateRetour')));
                $voyage->setType($request->request->get('type'));
                $voyage->setNbPlacesD((int)$request->request->get('nbPlacesD'));
                $voyage->setPrix((float)$request->request->get('prix'));
                $voyage->setDescription($request->request->get('description'));

                // Gestion de l'offre
                $idOffre = $request->request->get('id_offre');
                if ($idOffre) {
                    $offre = $offreRepository->find($idOffre);
                    $voyage->setId_offre($offre);
                } else {
                    $voyage->setId_offre(null);
                }

                // Gestion des images
                $uploadedFiles = $request->files->get('images');
                $existingImages = $request->request->all()['existing_images'] ?? [];

                // Si de nouvelles images sont uploadées, on les traite
                if ($uploadedFiles && count($uploadedFiles) > 0) {
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $imagePaths = [];

                    // Supprimer les anciennes images si nécessaire
                    // (À implémenter selon votre logique de gestion des fichiers)

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

                    // Si on a de nouvelles images, on les utilise
                    if (!empty($imagePaths)) {
                        $voyage->setPathImages(implode('***', $imagePaths));
                    }
                } elseif (!empty($existingImages)) {
                    // Sinon, on conserve les images existantes
                    $voyage->setPathImages(implode('***', $existingImages));
                }

                // Enregistrement en base de données
                $entityManager->persist($voyage);
                $entityManager->flush();

                $this->addFlash('success', 'Le voyage a été modifié avec succès !');
                return $this->redirectToRoute('app_voyages_agence');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification : '.$e->getMessage());
                return $this->redirectToRoute('app_modifier_voyage', ['id' => $voyage->getId()]);
            }
        }

        // Si la méthode n'est pas POST, rediriger vers la page d'édition
        return $this->redirectToRoute('app_modifier_voyage', ['id' => $voyage->getId()]);
    }
}