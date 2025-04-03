<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DetailsVoyageAgence extends AbstractController
{
    #[Route('/ModifierVoyage/{id}', name: 'app_modifier_voyage')]
    public function ModifierVoyage(Voyage $voyage,OffreRepository $offreRepository): Response
    {
        $offres = $offreRepository-> findOffresByAgence(1);

        return $this->render('voyages/ModifierVoyage.html.twig', [
            'voyage' => $voyage,
            'offres' => $offres,
        ]);
    }

    #[Route('/Supprimer/Voyage/{id}', name: 'app_supprimer_voyage', methods: ['POST'])]
    public function SupprimerVoyage(Voyage $voyage, EntityManagerInterface $entityManager, Request $request): Response {
        if ($this->isCsrfTokenValid('delete'.$voyage->getId(), $request->request->get('_token'))) {
            try {
                // Suppression du voyage de la base de données seulement
                $entityManager->remove($voyage);
                $entityManager->flush();

                $this->addFlash('success', 'Le voyage a été supprimé avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression : '.$e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide, impossible de supprimer le voyage.');
        }

        return $this->redirectToRoute('app_voyages_agence');
    }
}
