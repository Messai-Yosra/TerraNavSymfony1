<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Trajet;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TrajetClientController extends AbstractController
{
    #[Route('/trajets', name: 'app_trajets')]
    public function index(): Response
    {
        return $this->render('trajets/client_index.html.twig');
    }

    #[Route('/trajets/liste', name: 'client_trajets_list')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchTerm = $request->query->get('search');

        $queryBuilder = $entityManager->getRepository(Trajet::class)
            ->createQueryBuilder('t')
            ->where('t.id_user = :user')
            ->setParameter('user', $entityManager->getReference(Utilisateur::class, 251));

        if ($searchTerm) {
            $queryBuilder->andWhere('t.pointDepart LIKE :searchTerm OR t.destination LIKE :searchTerm')
                         ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        $trajets = $queryBuilder->getQuery()->getResult();

        return $this->render('trajets/client_trajets_list.html.twig', [
            'trajets' => $trajets,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/trajets/ajouter', name: 'client_trajet_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $trajet = new Trajet();

        $form = $this->createFormBuilder($trajet, [
            'attr' => ['id' => 'trajet-form'],
            'data_class' => Trajet::class
        ])
            ->add('pointDepart', TextType::class, [
                'label' => 'Point de départ',
                'attr' => ['class' => 'form-control'],
                
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control'],
                
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['class' => 'form-control'],
            
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
                
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($request->isXmlHttpRequest()) {
                if (!$form->isValid()) {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[$error->getOrigin()->getName()] = $error->getMessage();
                    }

                    return $this->json([
                        'success' => false,
                        'errors' => $errors
                    ], 422);
                }

                try {
                    $trajet->setId_User($em->getReference(Utilisateur::class, 251));
                    $em->persist($trajet);
                    $em->flush();

                    return $this->json([
                        'success' => true,
                        'title' => 'Succès',
                        'message' => 'Trajet créé avec succès',
                        'redirect' => $this->generateUrl('client_trajets_list')
                    ]);
                } catch (\Exception $e) {
                    return $this->json([
                        'success' => false,
                        'title' => 'Erreur',
                        'message' => 'Une erreur est survenue lors de la création: '.$e->getMessage()
                    ], 500);
                }
            }

            // Non-AJAX handling
            if ($form->isValid()) {
                try {
                    $trajet->setId_User($em->getReference(Utilisateur::class, 251));
                    $em->persist($trajet);
                    $em->flush();

                    $this->addFlash('success', 'Trajet créé avec succès');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création: '.$e->getMessage());
                }
                return $this->redirectToRoute('client_trajet_new');
            }
        }

        return $this->render('transports/Client_Trajet_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/trajets/modifier/{id}', name: 'client_trajet_edit')]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
    {
        if ($trajet->getId_User()->getId() !== 251) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres trajets');
            return $this->redirectToRoute('client_trajets_list');
        }

        $form = $this->createFormBuilder($trajet, [
            'attr' => ['id' => 'trajet-form'],
            'data_class' => Trajet::class
        ])
            ->add('pointDepart', TextType::class, [
                'label' => 'Point de départ',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à jour',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($request->isXmlHttpRequest()) {
                if ($form->isValid()) {
                    try {
                        $em->flush();

                        return new JsonResponse([
                            'success' => true,
                            'title' => 'Succès',
                            'message' => 'Trajet mis à jour avec succès',
                            'redirect' => $this->generateUrl('client_trajets_list')
                        ]);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'title' => 'Erreur',
                            'message' => 'Une erreur est survenue lors de la mise à jour: '.$e->getMessage()
                        ], 500);
                    }
                } else {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[$error->getOrigin()->getName()] = $error->getMessage();
                    }
                    return new JsonResponse([
                        'success' => false,
                        'title' => 'Erreur de validation',
                        'errors' => $errors
                    ], 422);
                }
            } else {
                if ($form->isValid()) {
                    try {
                        $em->flush();

                        $this->addFlash('success', 'Trajet mis à jour avec succès');
                        return $this->redirectToRoute('client_trajets_list');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour: '.$e->getMessage());
                    }
                }
            }
        }

        return $this->render('transports/Client_Trajet_edit.html.twig', [
            'form' => $form->createView(),
            'trajet' => $trajet,
        ]);
    }

    #[Route('/trajets/supprimer/{id}', name: 'client_trajet_delete')]
    public function delete(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
    {
        if ($trajet->getId_User()->getId() !== 251) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous ne pouvez supprimer que vos propres trajets'
                ], 403);
            }
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres trajets');
            return $this->redirectToRoute('client_trajets_list');
        }

        if (!$this->isCsrfTokenValid('delete'.$trajet->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], 403);
            }
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('client_trajets_list');
        }

        try {
            $em->remove($trajet);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Trajet supprimé avec succès',
                    'redirect' => $this->generateUrl('client_trajets_list')
                ]);
            }

            $this->addFlash('success', 'Trajet supprimé avec succès');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression'
                ], 500);
            }
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('client_trajets_list');
    }
}