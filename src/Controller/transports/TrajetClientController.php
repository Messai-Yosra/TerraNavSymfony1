<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Psr\Log\LoggerInterface; 
use App\Entity\Trajet;
use App\Entity\Transport;
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

    #[Route('/trajets/liste', name: 'client_trajets_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchTerm = $request->query->get('search', '');
        $isAjax = $request->isXmlHttpRequest();

        $queryBuilder = $entityManager->getRepository(Trajet::class)
            ->createQueryBuilder('t');

        if ($searchTerm) {
            $queryBuilder->where('t.pointDepart LIKE :searchTerm')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $trajets = $queryBuilder->getQuery()->getResult();

        if ($isAjax) {
            $trajetData = array_map(function ($trajet) {
                return [
                    'id' => $trajet->getId(),
                    'pointDepart' => $trajet->getPointDepart(),
                    'destination' => $trajet->getDestination(),
                    'dateDepart' => $trajet->getDateDepart()->format('d/m/Y H:i'),
                    'duree' => $trajet->getDuree(),
                    'disponibilite' => $trajet->getDisponibilite(),
                    'description' => $trajet->getDescription(),
                    'csrfToken' => $this->container->get('security.csrf.token_manager')->getToken('delete' . $trajet->getId())->getValue(),
                ];
            }, $trajets);

            return new JsonResponse([
                'trajets' => $trajetData,
                'searchTerm' => $searchTerm,
            ]);
        }

        return $this->render('transports/client_trajets_list.html.twig', [
            'trajets' => $trajets,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/trajets/ajouter', name: 'client_trajet_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un trajet.');
            return $this->redirectToRoute('app_login');
        }

        $trajet = new Trajet();
        $form = $this->createFormBuilder($trajet, [
            'attr' => ['id' => 'trajet-form'],
            'data_class' => Trajet::class
        ])
            ->add('pointDepart', TextType::class, [
                'label' => 'Point de départ',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le point de départ est requis']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le point de départ doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le point de départ ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La destination est requise']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'La destination doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La destination ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de départ est requise']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de départ doit être aujourd\'hui ou dans le futur',
                    ]),
                ],
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est requise']),
                    new Assert\Positive(['message' => 'La durée doit être positive']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 1440, // 24 hours
                        'notInRangeMessage' => 'La durée doit être entre {{ min }} et {{ max }} minutes',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
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
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $errors
                    ], 422);
                }

                try {
                    $em->persist($trajet);
                    $em->flush();

                    return new JsonResponse([
                        'success' => true,
                        'title' => 'Succès',
                        'message' => 'Trajet créé avec succès',
                        'redirect' => $this->generateUrl('client_trajets_list')
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'title' => 'Erreur',
                        'message' => 'Une erreur est survenue lors de la création: ' . $e->getMessage()
                    ], 500);
                }
            }

            if ($form->isValid()) {
                try {
                    $em->persist($trajet);
                    $em->flush();

                    $this->addFlash('success', 'Trajet créé avec succès');
                    return $this->redirectToRoute('client_trajets_list');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création: ' . $e->getMessage());
                    return $this->redirectToRoute('client_trajet_new');
                }
            }
        }

        return $this->render('transports/Client_Trajet_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/trajets/modifier/{id}', name: 'client_trajet_edit')]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
    {
       

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
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour effectuer cette action'
                ], 403);
            }
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action');
            return $this->redirectToRoute('app_login');
        }
        
        // Note: Nous supprimons la vérification du propriétaire pour permettre à tout utilisateur connecté de supprimer des trajets

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
                    'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
                ], 500);
            }
            $this->addFlash('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }

        return $this->redirectToRoute('client_trajets_list');
    }

    #[Route('/trajets/affecter/{transportId}', name: 'client_trajet_affect', methods: ['GET'])]
public function affect(int $transportId, Request $request, EntityManagerInterface $entityManager): Response
{
    $searchTerm = $request->query->get('search', '');
    $isAjax = $request->isXmlHttpRequest();

    // Validate transport existence
    $transport = $entityManager->getRepository(Transport::class)->find($transportId);
    if (!$transport) {
        $this->addFlash('error', 'Transport non trouvé.');
        return $this->redirectToRoute('client_transports_list');
    }

    $queryBuilder = $entityManager->getRepository(Trajet::class)
        ->createQueryBuilder('t')
        ->where('t.disponibilite = :disponible')
        ->setParameter('disponible', true);

    if ($searchTerm) {
        $queryBuilder->andWhere('t.pointDepart LIKE :searchTerm OR t.destination LIKE :searchTerm')
                     ->setParameter('searchTerm', '%' . $searchTerm . '%');
    }

    $trajets = $queryBuilder->getQuery()->getResult();

    if ($isAjax) {
        $trajetData = array_map(function ($trajet) use ($transportId) {
            return [
                'id' => $trajet->getId(),
                'pointDepart' => $trajet->getPointDepart(),
                'destination' => $trajet->getDestination(),
                'dateDepart' => $trajet->getDateDepart()->format('d/m/Y H:i'),
                'duree' => $trajet->getDuree(),
                'disponibilite' => $trajet->getDisponibilite(),
                'description' => $trajet->getDescription(),
                'transportId' => $transportId,
                'csrfToken' => $this->container->get('security.csrf.token_manager')->getToken('delete' . $trajet->getId())->getValue(),
            ];
        }, $trajets);

        return new JsonResponse([
            'trajets' => $trajetData,
            'searchTerm' => $searchTerm,
        ]);
    }

    return $this->render('transports/client_trajet_affect.html.twig', [
        'trajets' => $trajets,
        'searchTerm' => $searchTerm,
        'transportId' => $transportId,
    ]);
}
#[Route('/client/trajet/{id}', name: 'client_trajet_details')]
    public function details(Trajet $trajet, EntityManagerInterface $entityManager): Response
    {
        // Get other trajets (all available trajets, excluding the current one)
        $autresTrajets = $entityManager->getRepository(Trajet::class)->findBy(
            ['disponibilite' => true],
            ['dateDepart' => 'DESC'],
            6 // Limit to 6
        );
        $autresTrajets = array_filter($autresTrajets, fn($t) => $t->getId() !== $trajet->getId());

        return $this->render('transports/trajet_details.html.twig', [
            'trajet' => $trajet,
            'autresTrajets' => $autresTrajets,
        ]);
    }
}