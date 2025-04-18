<?php

namespace App\Controller\transports;

use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, 
    DateTimeType,
    IntegerType,
    TextareaType,
    SubmitType,
    CheckboxType
};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class TrajetAdminController extends AbstractController
{
    #[Route('/TrajetsAdmin/liste', name: 'admin_trajets_index')]
public function index(Request $request, EntityManagerInterface $em): Response
{
    $searchTerm = $request->query->get('search');
    
    $trajetsQuery = $em->getRepository(Trajet::class)->createQueryBuilder('t');
    
    if ($searchTerm) {
        $trajetsQuery->where('t.pointDepart LIKE :searchTerm')
                    ->setParameter('searchTerm', '%'.$searchTerm.'%');
    }
    
    $trajets = $trajetsQuery->getQuery()->getResult();
    
    return $this->render('transports/trajetAdmin.html.twig', [
        'trajets' => $trajets,
        'searchTerm' => $searchTerm
    ]);
}

    #[Route('/TrajetsAdmin/ajouter', name: 'admin_trajets_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $trajet = new Trajet();
        $trajet->setDisponibilite(true);

        $form = $this->createFormBuilder($trajet)
            ->add('pointDepart', TextType::class, [
                'label' => 'Point de départ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control datetime-picker']
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-success']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($trajet);
            $em->flush();

            $this->addFlash('success', 'Trajet créé avec succès');
            return $this->redirectToRoute('admin_trajets_index');
        }

        return $this->render('transports/trajetForm.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Créer un nouveau trajet',
            'button_label' => 'Enregistrer'
        ]);
    }

    #[Route('/TrajetsAdmin/modifier/{id}', name: 'admin_trajets_edit')]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($trajet)
            ->add('pointDepart', TextType::class, [
                'label' => 'Point de départ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control datetime-picker']
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Trajet mis à jour avec succès');
            return $this->redirectToRoute('admin_trajets_index');
        }

        return $this->render('transports/trajetForm.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Modifier le trajet',
            'button_label' => 'Mettre à jour',
            'trajet' => $trajet
        ]);
    }

    #[Route('/TrajetsAdmin/supprimer/{id}', name: 'admin_trajets_delete', methods: ['POST'])]
public function delete(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
{
    // Vérification du token CSRF
    if (!$this->isCsrfTokenValid('delete'.$trajet->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('admin_trajets_index');
    }

    try {
        // Vérifier s'il y a des transports associés
        if (count($trajet->getTransports()) > 0) {
            $this->addFlash('warning', 'Impossible de supprimer : des transports sont liés à ce trajet');
            return $this->redirectToRoute('admin_trajets_index');
        }

        $em->remove($trajet);
        $em->flush();
        $this->addFlash('success', 'Trajet supprimé avec succès');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de la suppression : '.$e->getMessage());
    }

    return $this->redirectToRoute('admin_trajets_index');
}
}