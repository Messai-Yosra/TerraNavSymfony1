<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Repository\Voyage\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModifierOffre extends AbstractController
{
    #[Route('/modifierOffre/{id}', name: 'app_modifier_offre', methods: ['GET'])]
    public function modifierOffre(int $id, OffreRepository $offreRepository): Response
    {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            $this->addFlash('error', 'Offre non trouvée');
            return $this->redirectToRoute('app_offres_agence');
        }

        return $this->render('voyages/ModifierOffre.html.twig', [
            'offre' => $offre
        ]);
    }

    #[Route('/modifierOffreBd/{id}', name: 'app_modifier_offre_bd', methods: ['POST'])]
    public function modifierOffreBd(
        Request $request,
        int $id,
        OffreRepository $offreRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $offre = $offreRepository->find($id);

        if (!$offre) {
            $this->addFlash('error', 'Offre non trouvée');
            return $this->redirectToRoute('app_offres_agence');
        }

        // Mise à jour des propriétés de l'offre
        $offre->setTitre($request->request->get('titre'));
        $offre->setReduction((float)$request->request->get('reduction'));
        $offre->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        $offre->setDateFin(new \DateTime($request->request->get('dateFin')));
        $offre->setDescription($request->request->get('description'));

        // Gestion de l'image uploadée
        $uploadedFile = $request->files->get('image');

        if ($uploadedFile) {
            $projectDir = $this->getParameter('kernel.project_dir');
            $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
            $relativePath = 'img/offres/'.$newFilename;
            $absolutePath = $projectDir.'/public/'.$relativePath;

            // Supprimer l'ancienne image si elle existe
            if ($offre->getImagePath() && file_exists($offre->getImagePath())) {
                unlink($offre->getImagePath());
            }

            // Déplacer le nouveau fichier
            $uploadedFile->move(
                $projectDir.'/public/img/offres/',
                $newFilename
            );

            // Stocker le chemin absolu Windows
            $windowsPath = str_replace('/', '\\', $absolutePath);
            $offre->setImagePath($windowsPath);
        }

        try {
            $entityManager->persist($offre);
            $entityManager->flush();

            $this->addFlash('success', 'L\'offre a été modifiée avec succès !');
            return $this->redirectToRoute('app_offres_agence');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : '.$e->getMessage());
            return $this->redirectToRoute('app_modifier_offre', ['id' => $id]);
        }
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