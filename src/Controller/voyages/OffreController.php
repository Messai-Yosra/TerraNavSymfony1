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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OffreController extends AbstractController
{
    #[Route('/AjoutOffre', name: 'app_ajout_offre', methods: ['GET', 'POST'])]
    public function ajoutOffre(Request $request, UtilisateurRepository $userRepository, ValidatorInterface $validator): Response
    {
        $user = $userRepository->find(1);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_ajout_offre');
        }

        if ($request->isMethod('POST')) {
            $offre = new Offre();
            $offre->setId_user($user);

            // Récupération des données avec valeurs par défaut
            $formData = [
                'titre' => trim($request->request->get('titre', '')),
                'description' => trim($request->request->get('description', '')),
                'reduction' => $request->request->get('reduction'),
                'dateDebut' => $request->request->get('dateDebut'),
                'dateFin' => $request->request->get('dateFin'),
                'image' => $request->files->get('image')
            ];

            // Gestion de la réduction avec validation
            try {
                $reductionValue = $formData['reduction'];
                if ($reductionValue === '' || $reductionValue === null) {
                    $offre->setReduction(null);
                } else {
                    $offre->setReduction((float)$reductionValue);
                }
            } catch (\Throwable $e) {
                $offre->setReduction(null);
            }

            // Affectation des autres champs
            $offre->setTitre($formData['titre']);
            $offre->setDescription($formData['description']);

            // Gestion des dates avec validation
            try {
                if (!empty($formData['dateDebut'])) {
                    $offre->setDateDebut(new \DateTime($formData['dateDebut']));
                }
                if (!empty($formData['dateFin'])) {
                    $offre->setDateFin(new \DateTime($formData['dateFin']));
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide');
                return $this->render('voyages/AjouterOffre.html.twig', [
                    'errors' => ['dateDebut' => 'Format de date invalide'],
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

                return $this->render('voyages/AjouterOffre.html.twig', [
                    'errors' => $errorMessages,
                    'form_data' => $formData
                ]);
            }

            // Gestion de l'image uploadée
            $uploadedFile = $formData['image'];
            $imagePath = null;

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
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    return $this->redirectToRoute('app_ajout_offre');
                }
            }

            if (!$imagePath) {
                $defaultImage = str_replace(
                    '/',
                    '\\',
                    $this->getParameter('kernel.project_dir').'/public/img/about-1.jpg'
                );
                $imagePath = file_exists($defaultImage) ? $defaultImage : null;
            }

            $offre->setImagePath($imagePath);

            // Stockage temporaire en session
            $request->getSession()->set('offre_temporaire', serialize($offre));
            return $this->redirectToRoute('app_confirmation_ajout_offre');
        }

        return $this->render('voyages/AjouterOffre.html.twig', [
            'errors' => [],
            'form_data' => null
        ]);
    }

    #[Route('/confirmationAjoutOffre', name: 'app_confirmation_ajout_offre')]
    public function confirmationAjoutOffre(Request $request): Response
    {
        $session = $request->getSession();
        $offre = $session->get('offre_temporaire');
        $offre = unserialize($offre);

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
        $offreData = unserialize($offreData);

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
}