<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Repository\Voyage\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ModifierOffre extends AbstractController
{
    #[Route('/ModifierOffre/{id}', name: 'app_modifier_offre')]
    public function modifierOffre(Offre $offre): Response
    {
        return $this->render('voyages/ModifierOffre.html.twig', [
            'offre' => $offre,
            'errors' => []
        ]);
    }

    #[Route('/ModifierOffrebd/{id}', name: 'app_modifier_offre_bd', methods: ['POST'])]
    public function modifierOffreBd(
        Request $request,
        Offre $offre,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('POST')) {
            // Récupération des données avec valeurs par défaut
            $formData = [
                'titre' => trim($request->request->get('titre', '')),
                'description' => trim($request->request->get('description', '')),
                'reduction' => $request->request->get('reduction'),
                'dateDebut' => $request->request->get('dateDebut'),
                'dateFin' => $request->request->get('dateFin'),
                'image' => $request->files->get('image'),
                'existing_image' => $request->request->get('existing_image')
            ];

            // Mise à jour des propriétés avec gestion des erreurs
            try {
                $offre->setTitre($formData['titre']);
                $offre->setDescription($formData['description']);

                // Gestion de la réduction
                $reductionValue = $formData['reduction'];
                if ($reductionValue === '' || $reductionValue === null) {
                    $offre->setReduction(null);
                } else {
                    $offre->setReduction((float)$reductionValue);
                }

                // Gestion des dates
                if (!empty($formData['dateDebut'])) {
                    $offre->setDateDebut(new \DateTime($formData['dateDebut']));
                }
                if (!empty($formData['dateFin'])) {
                    $offre->setDateFin(new \DateTime($formData['dateFin']));
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de données invalide');
                return $this->render('voyages/ModifierOffre.html.twig', [
                    'offre' => $offre,
                    'errors' => ['general' => 'Format de données invalide'],
                    'form_data' => $formData
                ]);
            }

            // Validation des données
            $errors = $validator->validate($offre);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $field = preg_replace('/^object\./', '', $error->getPropertyPath());
                    $errorMessages[$field] = $error->getMessage();
                }

                return $this->render('voyages/ModifierOffre.html.twig', [
                    'offre' => $offre,
                    'errors' => $errorMessages,
                    'form_data' => $formData
                ]);
            }

            // Gestion de l'image uploadée
            $uploadedFile = $formData['image'];
            $existingImage = $formData['existing_image'];

            if ($uploadedFile) {
                try {
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                    $uploadDir = $projectDir.'/public/img/offres/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $uploadedFile->move($uploadDir, $newFilename);
                    $imagePath = str_replace('/', '\\', $uploadDir.$newFilename);
                    $offre->setImagePath($imagePath);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    return $this->render('voyages/ModifierOffre.html.twig', [
                        'offre' => $offre,
                        'errors' => ['image' => 'Erreur lors de l\'upload de l\'image'],
                        'form_data' => $formData
                    ]);
                }
            } elseif ($existingImage) {
                $offre->setImagePath($existingImage);
            }

            try {
                $entityManager->persist($offre);
                $entityManager->flush();

                $this->addFlash('success', 'L\'offre a été modifiée avec succès !');
                return $this->redirectToRoute('app_offres_agence');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification : '.$e->getMessage());
                return $this->render('voyages/ModifierOffre.html.twig', [
                    'offre' => $offre,
                    'errors' => ['general' => 'Erreur lors de la sauvegarde'],
                    'form_data' => $formData
                ]);
            }
        }

        return $this->redirectToRoute('app_modifier_offre', ['id' => $offre->getId()]);
    }

    #[Route('/supprimerOffre/{id}', name: 'app_supprimer_offre', methods: ['POST'])]
    public function supprimerOffre(
        int $id,
        OffreRepository $offreRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            $this->addFlash('error', 'Offre non trouvée');
            return $this->redirectToRoute('app_offres_agence');
        }

        try {
            $entityManager->remove($offre);
            $entityManager->flush();

            $this->addFlash('success', 'L\'offre a été supprimée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression : '.$e->getMessage());
        }

        return $this->redirectToRoute('app_offres_agence');
    }
}