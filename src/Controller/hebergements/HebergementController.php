<?php

namespace App\Controller\hebergements;

use App\Entity\Hebergement;
use App\Entity\Utilisateur;
use App\Form\Hebergement1Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

// Correction de la route principale pour utiliser /hebergements au lieu de symfon
#[Route('/hebergements')]
final class HebergementController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Affiche la liste de tous les hébergements
     */
    #[Route('/', name: 'app_hebergement_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        try {
            $hebergements = $entityManager
                ->getRepository(Hebergement::class)
                ->findAll();
                
            return $this->render('hebergements/hebergement/index.html.twig', [
                'hebergements' => $hebergements,
                'type' => null,
                'ville' => null
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des hébergements: ' . $e->getMessage());
            return $this->render('hebergements/hebergement/index.html.twig', [
                'hebergements' => [],
                'type' => null,
                'ville' => null
            ]);
        }
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

    /**
     * Recherche d'hébergements par type ou ville
     */
    #[Route('/search', name: 'app_hebergement_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $type = $request->query->get('type');
        $ville = $request->query->get('ville');
        
        try {
            $repository = $entityManager->getRepository(Hebergement::class);
            
            // Préparation des critères de recherche
            $criteria = [];
            if ($type) {
                $criteria['type_hebergement'] = $type;
            }
            if ($ville) {
                $criteria['ville'] = $ville;
            }
            
            // Effectuer la recherche
            $hebergements = empty($criteria) ? $repository->findAll() : $repository->findBy($criteria);
            
            return $this->render('hebergements/hebergement/index.html.twig', [
                'hebergements' => $hebergements,
                'type' => $type,
                'ville' => $ville
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la recherche: ' . $e->getMessage());
            return $this->redirectToRoute('app_hebergement_index');
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

    #[Route('/{id}', name: 'app_hebergement_delete', methods: ['POST'])]
    public function delete(Request $request, Hebergement $hebergement, EntityManagerInterface $entityManager): Response
    {
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$hebergement->getId(), $request->getPayload()->getString('_token'))) {
            try {
                // Récupérer le nom de l'hébergement avant suppression pour le message
                $hebergementNom = $hebergement->getNom();
                
                $entityManager->remove($hebergement);
                $entityManager->flush();
                
                $this->addFlash('success', "L'hébergement \"$hebergementNom\" a été supprimé avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de l\'hébergement: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide. La suppression n\'a pas été effectuée.');
        }

        return $this->redirectToRoute('app_hebergement_index', [], Response::HTTP_SEE_OTHER);
    }
}
