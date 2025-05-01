<?php

namespace App\Controller\hebergements;

use App\Entity\Hebergement;
use App\Entity\Utilisateur;
use App\Form\Hebergement1Type;
use App\Service\ExcelImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;


#[Route('/hebergements')]
final class HebergementController extends AbstractController
{
    private $security;
    private $excelImportService;

    public function __construct(Security $security, ExcelImportService $excelImportService)
    {
        $this->security = $security;
        $this->excelImportService = $excelImportService;
    }


    #[Route('/new', name: 'app_hebergement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $hebergement = new Hebergement();
        $form = $this->createForm(Hebergement1Type::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($hebergement);
            $entityManager->flush();

            return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hebergements/hebergement/new.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form,
        ]);
    }

    // Améliorer la méthode d'ajout

    #[Route('/add', name: 'app_hebergement_add', methods: ['GET', 'POST'])]
    public function addHebergement(Request $request, EntityManagerInterface $entityManager): Response
    {
        $hebergement = new Hebergement();
        
        // Définir des valeurs par défaut
        $hebergement->setNoteMoyenne(0.0);
        $hebergement->setNbChambres(0);
        
        // Associer l'utilisateur connecté à l'hébergement
        $user = $this->security->getUser();
        if ($user instanceof Utilisateur) {
            $hebergement->setIdUser($user);
        }
        
        $form = $this->createForm(Hebergement1Type::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Vérifier à nouveau que l'utilisateur est associé avant la persistance
                    if (!$hebergement->getIdUser() && $user instanceof Utilisateur) {
                        $hebergement->setIdUser($user);
                    }
                    $entityManager->persist($hebergement);
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Hébergement ajouté avec succès!');
                    return $this->redirectToRoute('app_hebergement_show', ['id' => $hebergement->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'ajout: ' . $e->getMessage());
                }
            } else {
                // Quand le formulaire est invalide, ajouter un message flash
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez les corriger.');
            }
        }

        return $this->render('hebergements/hebergement/AddHebergement.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'app_hebergement_index', methods: ['GET'])]
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
                    if ($firstChambre) {
                        $images = $firstChambre->getImages();
                        if (!$images->isEmpty()) {
                            $firstImage = $images->first()->getUrlImage();
                        }
                    }
                }
                $hebergementImages[$hebergement->getId()] = $firstImage;
            }
          
            return $this->render('hebergements/hebergement/index.html.twig', [
                'hebergements' => $hebergements,
                'hebergementImages' => $hebergementImages,
                'types' => $types,
                'villes' => $villes,
                'type_selected' => null,
                'ville_selected' => null
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des hébergements: ' . $e->getMessage());
            return $this->render('hebergements/hebergement/index.html.twig', [
                'hebergements' => [],
                'hebergementImages' => [],
                'types' => [],
                'villes' => [],
                'type_selected' => null,
                'ville_selected' => null
            ]);
        }
    }

    #[Route('/hebergement/{id}/chambres', name: 'app_chambres_by_hebergement', methods: ['GET'])]
    public function chambresParHebergement(Hebergement $hebergement): Response
    {
        $chambres = $hebergement->getChambres();

        return $this->render('hebergements/chambre/chambres_par_hebergement.html.twig', [
            'hebergement' => $hebergement,
            'chambres' => $chambres,
        ]);
    }
    #[Route('/hebergementClient/{id}/chambres', name: 'app_chambres_by_hebergementC', methods: ['GET'])]
    public function chambresParHebergementClient(Hebergement $hebergement): Response
    {
        $chambres = $hebergement->getChambres();

        return $this->render('hebergements/chambre/ChambreParHebergementClient.html.twig', [
            'hebergement' => $hebergement,
            'chambres' => $chambres,
        ]);
    }

#[Route('/search', name: 'app_hebergement_search', methods: ['GET'])]
public function search(Request $request, EntityManagerInterface $entityManager): Response
{
    $type = $request->query->get('type');
    $ville = $request->query->get('ville');
    $maxPrice = $request->query->get('maxPrice');
    $minRooms = $request->query->get('nbChambres');

    try {
        $qb = $entityManager->createQueryBuilder()
            ->select('h')
            ->from(Hebergement::class, 'h');

        // Only join chambres if filtering by price
        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->innerJoin('h.chambres', 'c');
        } else {
            $qb->leftJoin('h.chambres', 'c');
        }

        if ($type) {
            $qb->andWhere('h.type_hebergement = :type')
               ->setParameter('type', $type);
        }

        if ($ville) {
            $qb->andWhere('h.ville = :ville')
               ->setParameter('ville', $ville);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->andWhere('c.prix <= :maxPrice')
               ->setParameter('maxPrice', (float)$maxPrice);
        }

        if ($minRooms !== null && $minRooms !== '') {
            $qb->andWhere('h.nb_chambres >= :minRooms')
               ->setParameter('minRooms', (int)$minRooms);
        }

        // Ensure unique results
        $qb->distinct();

        $hebergements = $qb->getQuery()->getResult();

        $types = $entityManager->createQuery('SELECT DISTINCT h.type_hebergement FROM App\Entity\Hebergement h ORDER BY h.type_hebergement')
            ->getResult();
        $types = array_column($types, 'type_hebergement');

        $villes = $entityManager->createQuery('SELECT DISTINCT h.ville FROM App\Entity\Hebergement h ORDER BY h.ville')
            ->getResult();
        $villes = array_column($villes, 'ville');

        $hebergementImages = [];
        foreach ($hebergements as $hebergement) {
            $firstImage = null;
            $chambres = $hebergement->getChambres();
            if (!$chambres->isEmpty()) {
                $firstChambre = $chambres->first();
                if ($firstChambre) {
                    $images = $firstChambre->getImages();
                    if (!$images->isEmpty()) {
                        $firstImage = $images->first()->getUrlImage();
                    }
                }
            }
            $hebergementImages[$hebergement->getId()] = $firstImage;
        }

        return $this->render('hebergements/hebergement/index.html.twig', [
            'hebergements' => $hebergements,
            'hebergementImages' => $hebergementImages,
            'types' => $types,
            'villes' => $villes,
            'type_selected' => $type,
            'ville_selected' => $ville,
            'maxPrice_selected' => $maxPrice,
            'nbChambres_selected' => $minRooms,
        ]);
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la recherche: ' . $e->getMessage());
        return $this->render('hebergements/hebergement/index.html.twig', [
            'hebergements' => [],
            'hebergementImages' => [],
            'types' => [],
            'villes' => [],
            'type_selected' => $type,
            'ville_selected' => $ville,
            'maxPrice_selected' => $maxPrice,
            'nbChambres_selected' => $minRooms,
        ]);
    }
}
#[Route('/searchClient', name: 'app_hebergement_search_client', methods: ['GET'])]
public function searchClient(Request $request, EntityManagerInterface $entityManager): Response
{
    $type = $request->query->get('type');
    $ville = $request->query->get('ville');
    $maxPrice = $request->query->get('maxPrice');
    $minRooms = $request->query->get('nbChambres');

    try {
        $qb = $entityManager->createQueryBuilder()
            ->select('h')
            ->from(Hebergement::class, 'h');

        // Only join chambres if filtering by price
        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->innerJoin('h.chambres', 'c');
        } else {
            $qb->leftJoin('h.chambres', 'c');
        }

        if ($type) {
            $qb->andWhere('h.type_hebergement = :type')
               ->setParameter('type', $type);
        }

        if ($ville) {
            $qb->andWhere('h.ville = :ville')
               ->setParameter('ville', $ville);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->andWhere('c.prix <= :maxPrice')
               ->setParameter('maxPrice', (float)$maxPrice);
        }

        if ($minRooms !== null && $minRooms !== '') {
            $qb->andWhere('h.nb_chambres >= :minRooms')
               ->setParameter('minRooms', (int)$minRooms);
        }

        // Ensure unique results
        $qb->distinct();

        $hebergements = $qb->getQuery()->getResult();

        $types = $entityManager->createQuery('SELECT DISTINCT h.type_hebergement FROM App\Entity\Hebergement h ORDER BY h.type_hebergement')
            ->getResult();
        $types = array_column($types, 'type_hebergement');

        $villes = $entityManager->createQuery('SELECT DISTINCT h.ville FROM App\Entity\Hebergement h ORDER BY h.ville')
            ->getResult();
        $villes = array_column($villes, 'ville');

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
            'type_selected' => $type,
            'ville_selected' => $ville,
            'maxPrice_selected' => $maxPrice,
            'nbChambres_selected' => $minRooms,
        ]);
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la recherche: ' . $e->getMessage());
        return $this->render('hebergements/hebergement/index.html.twig', [
            'hebergements' => [],
            'hebergementImages' => [],
            'types' => [],
            'villes' => [],
            'type_selected' => $type,
            'ville_selected' => $ville,
            'maxPrice_selected' => $maxPrice,
            'nbChambres_selected' => $minRooms,
        ]);
    }
}
    /**
     * Affiche les détails d'un hébergement spécifique
     */
    #[Route('/{id}', name: 'app_hebergement_show', methods: ['GET'])]
    public function show(Hebergement $hebergement = null): Response
    {
        // Vérification que l'hébergement existe
        if (!$hebergement) {
            $this->addFlash('error', 'Hébergement non trouvé');
            return $this->redirectToRoute('app_hebergement_index');
        }
        
        return $this->render('hebergements/hebergement/show.html.twig', [
            'hebergement' => $hebergement,
        ]);
    }

    // Améliorer la méthode edit existante

    #[Route('/{id}/edit', name: 'app_hebergement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        // Supprimer cette vérification qui bloque l'accès aux non-administrateurs
        // if (!$this->isGranted('ROLE_ADMIN')) {
        //     $this->addFlash('error', 'Vous n\'avez pas les droits nécessaires pour modifier cet hébergement.');
        //     return $this->redirectToRoute('app_hebergement_index');
        // }
        
        $form = $this->createForm(Hebergement1Type::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Hébergement modifié avec succès!');
                
                return $this->redirectToRoute('app_hebergement_show', ['id' => $hebergement->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification: ' . $e->getMessage());
            }
        }

        return $this->render('hebergements/hebergement/edit.html.twig', [
            'hebergement' => $hebergement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_hebergement_delete', methods: ['POST'])]
    public function delete(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$hebergement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($hebergement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/currency/change', name: 'app_change_currency', methods: ['POST'])]
    public function changeCurrency(Request $request): Response
    {
        $currency = $request->request->get('currency', 'auto');
        $request->getSession()->set('currency', $currency);
        
        return $this->redirectToRoute('app_hebergement_index');
    }

    #[Route('/import-excel', name: 'app_hebergement_import_excel', methods: ['POST'])]
    public function importExcel(Request $request): Response
    {
        $file = $request->files->get('excel_file');
        
        if (!$file) {
            $this->addFlash('error', 'Aucun fichier n\'a été uploadé.');
            return $this->redirectToRoute('app_hebergement_index');
        }

        $user = $this->security->getUser();
        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Vous devez être connecté pour importer des hébergements.');
            return $this->redirectToRoute('app_hebergement_index');
        }

        try {
            $results = $this->excelImportService->importHebergements($file, $user);
            
            if ($results['success'] > 0) {
                $this->addFlash('success', $results['success'] . ' hébergement(s) importé(s) avec succès.');
            } else {
                $this->addFlash('warning', 'Aucun hébergement n\'a été importé.');
            }
            
            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $this->addFlash('warning', $error);
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_hebergement_index');
    }
}
