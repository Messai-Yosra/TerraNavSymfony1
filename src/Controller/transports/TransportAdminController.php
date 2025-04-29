<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Transport;
use App\Entity\Trajet;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

final class TransportAdminController extends AbstractController
{
    // Page d'accueil statique
    #[Route('/TransportsAdmin', name: 'admin_transports')]
    public function index(): Response
    {
        return $this->render('transports/transportAdmin.html.twig');
    }

    #[Route('/TransportsAdmin/liste', name: 'admin_transports_list')]
public function adminList(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
{
    $searchTerm = $request->query->get('search', '');
    $isAjax = $request->isXmlHttpRequest();

    // Construction de la requête de base
    $qb = $em->createQueryBuilder()
        ->select('t')
        ->from(Transport::class, 't')
        ->orderBy('t.nom', 'ASC');

    // Filtre de recherche
    if (!empty($searchTerm)) {
        $qb->andWhere('LOWER(t.nom) LIKE LOWER(:searchTerm)')
           ->setParameter('searchTerm', '%'.$searchTerm.'%');
    }

    // Pagination
    $transports = $paginator->paginate(
        $qb->getQuery(),
        $request->query->getInt('page', 1),
        10
    );

    // Réponse AJAX
    if ($isAjax) {
        $html = $this->renderView('admin/transport/_list.html.twig', [
            'transports' => $transports
        ]);

        return new JsonResponse([
            'html' => $html,
            'count' => $transports->getTotalItemCount()
        ]);
    }

    return $this->render('transports/admin_transports_list.html.twig', [
        'transports' => $transports,
        'searchTerm' => $searchTerm
    ]);
}
    // Ajouter un nouveau transport
    #[Route('/TransportsAdmin/ajouter', name: 'admin_transport_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $transport = new Transport();
        
        $form = $this->createFormBuilder($transport)
            ->add('nom', TextType::class)
            ->add('type', TextType::class)
            ->add('capacite', IntegerType::class)
            ->add('prix', NumberType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('contact', TextType::class, ['required' => false])
            ->add('imagePath', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Image'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-success']
            ])
            ->getForm();
    
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $transport->setId_trajet($em->getReference(Trajet::class, 4));
            $transport->setId_user($em->getReference(Utilisateur::class, 251));
            
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imagePath')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/transports',
                    $newFilename
                );
                $transport->setImagePath('uploads/transports/'.$newFilename);
            }
            
            $em->persist($transport);
            $em->flush();
            
            $this->addFlash('success', 'Transport created successfully!');
            return $this->redirectToRoute('admin_transports_list');
        }
    
        return $this->render('transports/admin_new.html.twig', [
            'form' => $form->createView(),
            'static_user_id' => 251,
            'static_trajet_id' => 4
        ]);
    }

    // Modifier un transport existant
    #[Route('/TransportsAdmin/modifier/{id}', name: 'admin_transport_edit')]
    public function edit(Request $request, Transport $transport, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($transport)
            ->add('nom', TextType::class, ['label' => 'Nom du transport'])
            ->add('type', TextType::class, ['label' => 'Type (ex. avion, train)'])
            ->add('capacite', NumberType::class, ['label' => 'Capacité'])
            ->add('prix', NumberType::class, ['label' => 'Prix (DTN)'])
            ->add('description', TextType::class, ['label' => 'Description', 'required' => false])
            ->add('contact', TextType::class, ['label' => 'Contact', 'required' => false])
            ->add('save', SubmitType::class, ['label' => 'Mettre à jour'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_transports_list');
        }

        return $this->render('transports/admin_edit.html.twig', [
            'form' => $form->createView(),
            'transport' => $transport,
        ]);
    }

    // Supprimer un transport
    #[Route('/TransportsAdmin/supprimer/{id}', name: 'admin_transport_delete')]
    public function delete(Request $request, Transport $transport, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$transport->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_transports_list');
        }

        try {
            $em->remove($transport);
            $em->flush();
            $this->addFlash('success', 'Transport supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du transport.');
        }

        return $this->redirectToRoute('admin_transports_list');
    }
    #[Route('/transports/exporter/pdf', name: 'client_transports_export_pdf', methods: ['GET'])]
    public function exportPdf(EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $transports = $entityManager->getRepository(Transport::class)
            ->createQueryBuilder('t')
            ->where('t.id_user = :user')
            ->setParameter('user', $entityManager->getReference(Utilisateur::class, 251))
            ->getQuery()
            ->getResult();

        $html = $this->renderView('transports/client_transports_pdf.html.twig', [
            'transports' => $transports,
        ]);

        try {
            $logger->info('Generating PDF with Dompdf');

            // Configure Dompdf options
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); // Enable if your template includes external resources like images

            // Instantiate Dompdf
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();
            $logger->info('PDF generated successfully');

            $filename = 'transports_list_' . date('Ymd_His') . '.pdf';

            return new Response(
                $pdfOutput,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        $filename
                    ),
                ]
            );
        } catch (\Exception $e) {
            $logger->error('PDF generation failed: ' . $e->getMessage());
            throw $e; // Remove this in production; redirect with flash message instead
        }
    }
}