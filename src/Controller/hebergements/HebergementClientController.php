<?php


namespace App\Controller\hebergements;

use App\Entity\Hebergement;
use App\Form\HebergementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HebergementClientController extends AbstractController
{
    #[Route('/HebergementsClient', name: 'app_hebergements')]
    public function index(EntityManagerInterface $entityManager): Response
    {

        try {
            $hebergements = $entityManager
                ->getRepository(Hebergement::class)
                ->findAll();

            // Fetch unique types and villes for the search form
            $types = $entityManager->createQuery('SELECT DISTINCT h.type_hebergement FROM App\Entity\Hebergement h ORDER BY h.type_hebergement')
                ->getResult();
            $types = array_column($types, 'type_hebergement');

            $villes = $entityManager->createQuery('SELECT DISTINCT h.ville FROM App\Entity\Hebergement h ORDER BY h.ville')
                ->getResult();
            $villes = array_column($villes, 'ville');

            // Prepare an array to store the first image for each hébergement
            $hebergementImages = [];
            foreach ($hebergements as $hebergement) {
                $firstImage = null;
                $chambres = $hebergement->getChambres();
                if (!$chambres->isEmpty()) {
                    $firstChambre = $chambres->first();
                    $images = $firstChambre->getImages();
                    if (!$images->isEmpty()) {
                        $firstImage = $images->first()->getUrlImage();
                    }
                }
                $hebergementImages[$hebergement->getId()] = $firstImage;
            }

            return $this->render('hebergements/hebergementClient.html.twig', [
                'hebergements' => $hebergements,
                'hebergementImages' => $hebergementImages,
                'types' => $types,
                'villes' => $villes,
                'type_selected' => null,
                'ville_selected' => null
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des hébergements: ' . $e->getMessage());
            return $this->render('hebergements/hebergementClient.html.twig', [
                'hebergements' => [],
                'hebergementImages' => [],
                'types' => [],
                'villes' => [],
                'type_selected' => null,
                'ville_selected' => null
            ]);
        }  }

    #[Route('/FormAjout', name: 'formajout')]
    public function add(): Response
    {
        $hebergement = new Hebergement();
        $form = $this->createForm(HebergementType::class, $hebergement);

        return $this->render('hebergements/hebergement/AddHebergement.html.twig', [
            'form' => $form->createView()
        ]);
    }
}