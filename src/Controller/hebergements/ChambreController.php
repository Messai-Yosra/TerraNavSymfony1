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

#[Route('/chambre')]
final class ChambreController extends AbstractController
{
    #[Route('/', name: 'app_chambre_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
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

        // Pagination
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($queryBuilder);
        $paginator
            ->getQuery()
            ->setFirstResult(($page - 1) * 9)
            ->setMaxResults(9);

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
            'totalItems' => count($paginator),
            'currentPage' => $page,
            'itemsPerPage' => 9,
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
            return $this->redirectToRoute('app_chambre_index', [], Response::HTTP_SEE_OTHER);
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
        $form = $this->createForm(ChambreType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_chambre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('hebergements/chambre/edit.html.twig', [
            'chambre' => $chambre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chambre_delete', methods: ['POST'])]
    public function delete(Request $request, Chambre $chambre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$chambre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chambre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_chambre_index', [], Response::HTTP_SEE_OTHER);
    }
}
