<?php

namespace App\Controller\hebergements;
use App\Entity\Hebergement;
use App\Entity\Image;
use App\Form\ImageType;
use App\Service\hebergements\FileUploader;
use App\Entity\Chambre;
use App\Form\ChambreType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use App\Entity\Utilisateur;

#[Route('/chambre')]
final class ChambreController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_chambre_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 9;
        $hebergementId = $request->query->get('hebergement', '');
        $disponibilite = $request->query->get('disponibilite', '');
        $capacite = $request->query->get('capacite', '');
        $maxPrice = $request->query->get('maxPrice', '');

        // Build the query
        $queryBuilder = $entityManager
            ->getRepository(Chambre::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.images', 'i')
            ->addSelect('i')
            ->leftJoin('c.id_hebergement', 'h')
            ->addSelect('h')
            ->orderBy('c.numero', 'ASC');

        // Apply filters
        if ($hebergementId !== '') {
            $queryBuilder->andWhere('h.id = :hebergementId')
                ->setParameter('hebergementId', $hebergementId);
        }
        if ($disponibilite !== '') {
            $queryBuilder->andWhere('c.disponibilite = :disponibilite')
                ->setParameter('disponibilite', $disponibilite === '1');
        }
        if ($capacite !== '') {
            $queryBuilder->andWhere('c.capacite = :capacite')
                ->setParameter('capacite', $capacite);
        }
        if ($maxPrice !== '') {
            $queryBuilder->andWhere('c.prix <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        // Get total items
        $totalItems = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Add pagination
        $paginator = $queryBuilder
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        // Fetch filter options
        $hebergements = $entityManager->getRepository(Hebergement::class)->findBy([], ['nom' => 'ASC']);
        $capacites = $entityManager->createQueryBuilder()
            ->select('DISTINCT c.capacite')
            ->from(Chambre::class, 'c')
            ->where('c.capacite IS NOT NULL')
            ->orderBy('c.capacite', 'ASC')
            ->getQuery()
            ->getScalarResult();
        $capacites = array_column($capacites, 'capacite');

        return $this->render('hebergements/chambre/index.html.twig', [
            'chambres' => $paginator,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'hebergements' => $hebergements,
            'capacites' => $capacites,
            'hebergement_selected' => $hebergementId,
            'disponibilite_selected' => $disponibilite,
            'capacite_selected' => $capacite,
            'maxPrice_selected' => $maxPrice,
        ]);
    }
    #[Route('/new', name: 'app_chambre_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): Response {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $chambre = new Chambre();
        $form = $this->createForm(ChambreType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($chambre);
            $entityManager->flush();

            // Handle image uploads
            $uploadedFiles = $form->get('images')->getData();
            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile) {
                        $image = new Image();
                        $fileName = $fileUploader->upload($uploadedFile);
                        $image->setUrlImage('/ChambreImages/'.$fileName);
                        $image->setIdChambre($chambre);
                        $entityManager->persist($image);
                    }
                }
                $entityManager->flush();
            }

            $this->addFlash('success', 'Chambre créée avec succès!');
            return $this->redirectToRoute('app_chambres_by_hebergement', [
                'id' => $chambre->getId_hebergement()->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hebergements/chambre/new.html.twig', [
            'chambre' => $chambre,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}', name: 'app_chambre_show', methods: ['GET'])]
    public function show(Chambre $chambre): Response
    {
        return $this->render('hebergements/chambre/show.html.twig', [
            'chambre' => $chambre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_chambre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Chambre $chambre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est le propriétaire de l'hébergement ou s'il est une agence
        if ($user instanceof Utilisateur) {
            $userRole = strtolower($user->getRole() ?? '');
            $isAgence = $userRole === 'agence';
            
            // Si ce n'est pas une agence, vérifier s'il est propriétaire
            if (!$isAgence && $chambre->getId_hebergement()->getIdUser() !== $user) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour modifier cette chambre.');
            return $this->redirectToRoute('app_chambre_index');
            }
        }

        $form = $this->createForm(ChambreType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Chambre modifiée avec succès!');

            return $this->redirectToRoute('app_chambres_by_hebergement', [
                'id' => $chambre->getId_hebergement()->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hebergements/chambre/edit.html.twig', [
            'chambre' => $chambre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chambre_delete', methods: ['POST'])]
    public function delete(Request $request, Chambre $chambre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est le propriétaire de l'hébergement ou s'il est une agence
        if ($user instanceof Utilisateur) {
            $userRole = strtolower($user->getRole() ?? '');
            $isAgence = $userRole === 'agence';
            
            // Si ce n'est pas une agence, vérifier s'il est propriétaire
            if (!$isAgence && $chambre->getId_hebergement()->getIdUser() !== $user) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer cette chambre.');
                
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['success' => false, 'message' => 'Vous n\'avez pas les droits pour supprimer cette chambre.'], Response::HTTP_FORBIDDEN);
                }
                
            return $this->redirectToRoute('app_chambre_index');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$chambre->getId(), $request->getPayload()->getString('_token'))) {
            try {
            $hebergementId = $chambre->getId_hebergement()->getId();
            $entityManager->remove($chambre);
            $entityManager->flush();
            $this->addFlash('success', 'Chambre supprimée avec succès!');

                // Si c'est une requête AJAX, retourner une réponse JSON
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['success' => true]);
                }

                // Récupérer l'URL de retour depuis le formulaire si elle existe
                $referer = $request->headers->get('referer');
                if ($referer) {
                    return $this->redirect($referer);
                }
                
                // Redirection par défaut si pas d'URL de retour
            return $this->redirectToRoute('app_chambres_by_hebergement', [
                'id' => $hebergementId
            ], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                    return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
            }
        } else {
            if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Récupérer l'URL de retour depuis le formulaire si elle existe
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_chambre_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/change-currency', name: 'app_change_currency', methods: ['POST'])]
    public function changeCurrency(Request $request): JsonResponse
    {
        $currency = $request->request->get('currency', 'auto');
        $request->getSession()->set('currency', $currency);
        
        return new JsonResponse(['success' => true]);
    }
}
