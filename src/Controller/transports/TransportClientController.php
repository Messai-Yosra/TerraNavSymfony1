<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Transport;
use App\Entity\Utilisateur;
use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

final class TransportClientController extends AbstractController
{
    #[Route('/transports', name: 'app_transports')]
    public function index(): Response
    {
        return $this->render('transports/client_index.html.twig');
    }

    #[Route('/transports/liste', name: 'client_transports_list')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchTerm = $request->query->get('search');
        
        $queryBuilder = $entityManager->getRepository(Transport::class)
            ->createQueryBuilder('t')
            ->where('t.id_user = :user')
            ->setParameter('user', $entityManager->getReference(Utilisateur::class, 251));
        
        if ($searchTerm) {
            $queryBuilder->andWhere('t.nom LIKE :searchTerm')
                         ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
        
        $transports = $queryBuilder->getQuery()->getResult();

        return $this->render('transports/client_transports_list.html.twig', [
            'transports' => $transports,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/transports/ajouter', name: 'client_transport_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $transport = new Transport();
        
        $form = $this->createFormBuilder($transport, [
            'attr' => ['id' => 'transport-form'],
            'data_class' => Transport::class
        ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du transport',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du transport est requis']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, chiffres, espaces ou tirets',
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de transport',
                'choices' => [
                    'Voiture privée' => 'Voiture privée',
                    'Taxi' => 'Taxi',
                    'Bus' => 'Bus',
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Choisissez un type',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un type de transport']),
                    new Assert\Choice([
                        'choices' => ['Voiture privée', 'Taxi', 'Bus'],
                        'message' => 'Type de transport invalide',
                    ]),
                ],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La capacité est requise']),
                    new Assert\Positive(['message' => 'La capacité doit être positive']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 500,
                        'notInRangeMessage' => 'La capacité doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est requis']),
                    new Assert\Positive(['message' => 'Le prix doit être positif']),
                    new Assert\Range([
                        'min' => 0.01,
                        'max' => 10000,
                        'notInRangeMessage' => 'Le prix doit être entre {{ min }}€ et {{ max }}€',
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
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contact est requis']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 20,
                        'minMessage' => 'Le contact doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le contact ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|[\+]?[0-9\s\-()]{5,20})$/',
                        'message' => 'Veuillez entrer un email ou numéro de téléphone valide',
                    ]),
                ],
            ])
            ->add('imagePath', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Image',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader une image JPEG ou PNG',
                        'maxSizeMessage' => 'La taille maximale est de {{ limit }}',
                    ]),
                ],
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
                    $transport->setId_User($em->getReference(Utilisateur::class, 251));
                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));
                    
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
                    
                    return $this->json([
                        'success' => true,
                        'title' => 'Succès',
                        'message' => 'Transport créé avec succès',
                        'redirect' => $this->generateUrl('client_transports_list')
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
                    $transport->setId_User($em->getReference(Utilisateur::class, 251));
                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));
                    
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
                    
                    $this->addFlash('success', 'Transport créé avec succès');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création: '.$e->getMessage());
                }
                return $this->redirectToRoute('client_transport_new');
            }
        }
    
        return $this->render('transports/Client_Transport_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/transports/modifier/{id}', name: 'client_transport_edit')]
public function edit(Request $request, Transport $transport, EntityManagerInterface $em): Response
{
    if ($transport->getId_User()->getId() !== 251) {
        $this->addFlash('error', 'Vous ne pouvez modifier que vos propres transports');
        return $this->redirectToRoute('client_transports_list');
    }

    $form = $this->createFormBuilder($transport, [
        'attr' => ['id' => 'transport-form'],
        'data_class' => Transport::class
    ])
    ->add('nom', TextType::class, [
        'label' => 'Nom du transport',
        'attr' => ['class' => 'form-control'],
    ])
        ->add('type', ChoiceType::class, [
            'label' => 'Type de transport',
            'choices' => [
                'Voiture privée' => 'Voiture privée',
                'Taxi' => 'Taxi',
                'Bus' => 'Bus',
            ],
            'attr' => ['class' => 'form-control'],
            'placeholder' => 'Choisissez un type',
        ])
        ->add('capacite', IntegerType::class, [
            'label' => 'Capacité',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('prix', NumberType::class, [
            'label' => 'Prix (€)',
            'scale' => 2,
            'attr' => ['class' => 'form-control'],
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'attr' => ['class' => 'form-control', 'rows' => 4],
        ])
        ->add('contact', TextType::class, [
            'label' => 'Contact',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('imagePath', FileType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Image',
            'attr' => ['class' => 'form-control'],
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
                    /** @var UploadedFile $imageFile */
                    $imageFile = $form->get('imagePath')->getData();
                    if ($imageFile) {
                        // Delete old image if exists
                        if ($transport->getImagePath()) {
                            $oldImage = $this->getParameter('kernel.project_dir').'/public/'.$transport->getImagePath();
                            if (file_exists($oldImage)) {
                                unlink($oldImage);
                            }
                        }
                        $newFilename = uniqid().'.'.$imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/transports',
                            $newFilename
                        );
                        $transport->setImagePath('uploads/transports/'.$newFilename);
                    }

                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));
                    $em->flush();

                    return new JsonResponse([
                        'success' => true,
                        'title' => 'Succès',
                        'message' => 'Transport mis à jour avec succès',
                        'redirect' => $this->generateUrl('client_transports_list')
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
                    /** @var UploadedFile $imageFile */
                    $imageFile = $form->get('imagePath')->getData();
                    if ($imageFile) {
                        // Delete old image if exists
                        if ($transport->getImagePath()) {
                            $oldImage = $this->getParameter('kernel.project_dir').'/public/'.$transport->getImagePath();
                            if (file_exists($oldImage)) {
                                unlink($oldImage);
                            }
                        }
                        $newFilename = uniqid().'.'.$imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/transports',
                            $newFilename
                        );
                        $transport->setImagePath('uploads/transports/'.$newFilename);
                    }

                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));
                    $em->flush();

                    $this->addFlash('success', 'Transport mis à jour avec succès');
                    return $this->redirectToRoute('client_transports_list');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour: '.$e->getMessage());
                }
            }
        }
    }

    return $this->render('transports/Client_Transport_edit.html.twig', [
        'form' => $form->createView(),
        'transport' => $transport,
    ]);
}
    #[Route('/transports/supprimer/{id}', name: 'client_transport_delete')]
    public function delete(Request $request, Transport $transport, EntityManagerInterface $em): Response
    {
        if ($transport->getId_User()->getId() !== 251) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous ne pouvez supprimer que vos propres transports'
                ], 403);
            }
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres transports');
            return $this->redirectToRoute('client_transports_list');
        }

        if (!$this->isCsrfTokenValid('delete'.$transport->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Token CSRF invalide'
                ], 403);
            }
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('client_transports_list');
        }

        try {
            $em->remove($transport);
            $em->flush();
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Transport supprimé avec succès',
                    'redirect' => $this->generateUrl('client_transports_list')
                ]);
            }
            
            $this->addFlash('success', 'Transport supprimé avec succès');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression'
                ], 500);
            }
            $this->addFlash('error', 'Erreur lors de la suppression');
        }

        return $this->redirectToRoute('client_transports_list');
    }
}