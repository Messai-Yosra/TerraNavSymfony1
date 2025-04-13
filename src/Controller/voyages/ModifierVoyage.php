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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ModifierVoyage extends AbstractController
{
    #[Route('/ModifierVoyage/{id}', name: 'app_modifier_voyage')]
    public function ModifierVoyage(Voyage $voyage, OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository->findOffresByAgence(1);

        return $this->render('voyages/ModifierVoyage.html.twig', [
            'voyage' => $voyage,
            'offres' => $offres,
            'errors' => []
        ]);
    }

    #[Route('/ModifierVoyagebd/{id}', name: 'app_modifier_voyage_bd', methods: ['POST'])]
    public function ModifierVoyagebd(
        Request $request,
        Voyage $voyage,
        OffreRepository $offreRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
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

                // Validation des données
                $errors = $validator->validate($voyage);

                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                    }

                    $offres = $offreRepository->findOffresByAgence(1);
                    return $this->render('voyages/ModifierVoyage.html.twig', [
                        'voyage' => $voyage,
                        'offres' => $offres,
                        'errors' => $errorMessages
                    ]);
                }

                // Gestion des images
                $uploadedFiles = $request->files->get('images');
                $existingImages = $request->request->all()['existing_images'] ?? [];

                if ($uploadedFiles && count($uploadedFiles) > 0) {
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $imagePaths = [];

                    foreach ($uploadedFiles as $uploadedFile) {
                        if ($uploadedFile) {
                            $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                            $relativePath = 'img/voyages/'.$newFilename;
                            $absolutePath = $projectDir.'/public/'.$relativePath;

                            $uploadedFile->move(
                                $projectDir.'/public/img/voyages/',
                                $newFilename
                            );

                            $windowsPath = str_replace('/', '\\', $absolutePath);
                            $imagePaths[] = $windowsPath;
                        }
                    }

                    if (!empty($imagePaths)) {
                        $voyage->setPathImages(implode('***', $imagePaths));
                    }
                } elseif (!empty($existingImages)) {
                    $voyage->setPathImages(implode('***', $existingImages));
                }

                $entityManager->persist($voyage);
                $entityManager->flush();

                $this->addFlash('success', 'Le voyage a été modifié avec succès !');
                return $this->redirectToRoute('app_voyages_agence');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification : '.$e->getMessage());
                return $this->redirectToRoute('app_modifier_voyage', ['id' => $voyage->getId()]);
            }
        }

        return $this->redirectToRoute('app_modifier_voyage', ['id' => $voyage->getId()]);
    }
}