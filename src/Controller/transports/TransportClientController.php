<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Validator\Constraints\DistanceConstraint;
use App\Service\IpCountryService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use App\Entity\Transport;
use App\Entity\Utilisateur;
use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\CityAutocompleter;
use Symfony\Component\HttpClient\HttpClient;



final class TransportClientController extends AbstractController
{
   
    #[Route('/transports', name: 'app_transports')]
    public function index(Request $request): Response
    {
        $form = $this->createFormBuilder(null, [
            'constraints' => [
                new DistanceConstraint(), // Appliquer la contrainte au niveau du formulaire
            ],
        ])
            ->add('departure', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le lieu de départ est requis']),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
                'attr' => ['class' => 'form-control', 'autocomplete' => 'off']
            ])
            ->add('destination', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La destination est requise']),
                    new Assert\Length(['min' => 2, 'max' => 50]),
                ],
                'attr' => ['class' => 'form-control', 'autocomplete' => 'off']
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date est requise']),
                    new Assert\GreaterThanOrEqual('today'),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('passagers', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de passagers est requis']),
                    new Assert\Range(['min' => 1, 'max' => 6]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            return $this->redirectToRoute('app_transport_search', [
                'departure' => $data['departure'],
                'destination' => $data['destination'],
                'date' => $data['date']->format('Y-m-d'),
                'passengers' => $data['passagers']
            ]);
        }

        return $this->render('transports/transportClient.html.twig', [
            'form' => $form->createView(),
            'mapbox_access_token' => $this->getParameter('mapbox_access_token'), // Pass the token
        ]);
    }

    #[Route('/transports/liste', name: 'client_transports_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchTerm = $request->query->get('search', '');
        $isAjax = $request->isXmlHttpRequest();

        // Récupérer l'utilisateur actuellement connecté
        $user = $this->getUser();

        if (!$user) {
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour accéder à cette fonctionnalité'
                ], 403);
            }
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }

        $queryBuilder = $entityManager->getRepository(Transport::class)
            ->createQueryBuilder('t')
            ->where('t.id_user = :user')
            ->andWhere('t.id IS NOT NULL')
            ->andWhere('t.id > 0')
            ->setParameter('user', $user);

        if ($searchTerm) {
            $queryBuilder->andWhere('t.nom LIKE :searchTerm')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $transports = $queryBuilder->getQuery()->getResult();

        if ($isAjax) {
            $transportData = array_map(function ($transport) {
                $id = $transport->getId();
                if (!$id || !is_int($id) || $id <= 0) {
                    error_log('Invalid transport ID detected: ' . json_encode([
                        'id' => $id,
                        'nom' => $transport->getNom(),
                        'type' => $transport->getType(),
                    ]));
                    return null; // Exclude invalid transport
                }
                return [
                    'id' => $id,
                    'nom' => $transport->getNom(),
                    'type' => $transport->getType(),
                    'capacite' => $transport->getCapacite(),
                    'prix' => $transport->getPrix(),
                    'contact' => $transport->getContact(),
                    'description' => $transport->getDescription(),
                    'imagePath' => $transport->getImagePath(),
                    'csrfToken' => $this->container->get('security.csrf.token_manager')->getToken('delete' . $id)->getValue(),
                ];
            }, $transports);

            // Filter out null entries
            $transportData = array_filter($transportData);

            return new JsonResponse([
                'transports' => array_values($transportData),
                'searchTerm' => $searchTerm,
            ]);
        }

        return $this->render('transports/client_transports_list.html.twig', [
            'transports' => $transports,
            'searchTerm' => $searchTerm,
        ]);
    }



    #[Route('/transports/ajouter', name: 'client_transport_new')]
    public function new(Request $request, EntityManagerInterface $em, IpCountryService $ipCountryService): Response
    {
        $transport = new Transport();

        $clientIp = $request->getClientIp();
        $countryCode = $ipCountryService->getCountryCodeFromIp($clientIp);
        $phoneCode = $countryCode ? $ipCountryService->getPhoneCodeFromCountryCode($countryCode) : '';

        // Créer le formulaire
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
                'label' => 'Prix (DTN)',
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est requis']),
                    new Assert\Positive(['message' => 'Le prix doit être positif']),
                    new Assert\Range([
                        'min' => 0.01,
                        'max' => 10000,
                        'notInRangeMessage' => 'Le prix doit être entre {{ min }}DTN et {{ max }}DTN',
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
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $phoneCode ? 'Ex: ' . $phoneCode . '123456789' : 'Ex: +21612345678 ou email',
                ],
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
                    // Récupérer l'utilisateur connecté
                    $user = $this->getUser();
                    if (!$user) {
                        return $this->json([
                            'success' => false,
                            'title' => 'Erreur',
                            'message' => 'Vous devez être connecté pour effectuer cette action'
                        ], 403);
                    }

                    $transport->setId_User($user);
                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));

                    $imageFile = $form->get('imagePath')->getData();
                    if ($imageFile) {
                        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/transports',
                            $newFilename
                        );
                        $transport->setImagePath('uploads/transports/' . $newFilename);
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
                        'message' => 'Une erreur est survenue lors de la création: ' . $e->getMessage()
                    ], 500);
                }
            }

            // Non-AJAX handling
            if ($form->isValid()) {
                try {
                    // Récupérer l'utilisateur connecté
                    $user = $this->getUser();
                    if (!$user) {
                        $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action');
                        return $this->redirectToRoute('client_transport_new');
                    }

                    $transport->setId_User($user);
                    $transport->setId_Trajet($em->getReference(Trajet::class, 4));

                    $imageFile = $form->get('imagePath')->getData();
                    if ($imageFile) {
                        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/transports',
                            $newFilename
                        );
                        $transport->setImagePath('uploads/transports/' . $newFilename);
                    }

                    $em->persist($transport);
                    $em->flush();

                    $this->addFlash('success', 'Transport créé avec succès');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création: ' . $e->getMessage());
                }
                return $this->redirectToRoute('client_transport_new');
            }
        }

        return $this->render('transports/Client_Transport_new.html.twig', [
            'form' => $form->createView(),
            'phoneCode' => $phoneCode,
        ]);
    }

   

    #[Route('/transports/modifier/{id}', name: 'client_transport_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id, EntityManagerInterface $em): Response
    {
        // Validate ID as integer
        if (!is_numeric($id) || (int)$id <= 0) {
            error_log("Invalid transport ID provided: $id");
            $this->addFlash('error', 'ID de transport invalide.');
            return $this->redirectToRoute('client_transports_list');
        }

        $transport = $em->getRepository(Transport::class)->find((int)$id);

        if (!$transport) {
            $this->addFlash('error', 'Transport non trouvé pour l\'ID ' . $id);
            return $this->redirectToRoute('client_transports_list');
        }

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
                'label' => 'Prix (DTN)',
                'scale' => 2,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est requis']),
                    new Assert\Positive(['message' => 'Le prix doit être positif']),
                    new Assert\Range([
                        'min' => 0.01,
                        'max' => 10000,
                        'notInRangeMessage' => 'Le prix doit être entre {{ min }}DTN et {{ max }}DTN',
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
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($request->isXmlHttpRequest()) {
                if ($form->isValid()) {
                    try {
                        $imageFile = $form->get('imagePath')->getData();
                        if ($imageFile) {
                            $newFilename = uniqid().'.'.$imageFile->guessExtension();
                            $imageFile->move(
                                $this->getParameter('kernel.project_dir').'/public/uploads/transports',
                                $newFilename
                            );
                            $transport->setImagePath('uploads/transports/'.$newFilename);
                        }

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
                            'message' => 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage()
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
                        $imageFile = $form->get('imagePath')->getData();
                        if ($imageFile) {
                            $newFilename = uniqid().'.'.$imageFile->guessExtension();
                            $imageFile->move(
                                $this->getParameter('kernel.project_dir').'/public/uploads/transports',
                                $newFilename
                            );
                            $transport->setImagePath('uploads/transports/'.$newFilename);
                        }

                        $em->flush();
                        $this->addFlash('success', 'Transport mis à jour avec succès');
                        return $this->redirectToRoute('client_transports_list');
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour: ' . $e->getMessage());
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

        // Vérifier que l'utilisateur est le propriétaire du transport
        if ($transport->getId_User() !== $user) {
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


    #[Route('/transports/search', name: 'app_transport_search')]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupération des paramètres
        $departure = $request->query->get('departure');
        $minPrice = $request->query->get('minPrice', 0);
        $maxPrice = $request->query->get('maxPrice', 1000);
        $minCapacity = $request->query->get('minCapacity', null);
        $type = $request->query->get('type', 'all');

        // Construction de la requête
        $queryBuilder = $entityManager->getRepository(Transport::class)
            ->createQueryBuilder('t')
            ->join('t.id_trajet', 'tr');

        // Filtre par point de départ
        if ($departure) {
            $queryBuilder->where('tr.pointDepart LIKE :departure')
                         ->setParameter('departure', '%' . $departure . '%');
        }

        // Filtre par prix
        $queryBuilder->andWhere('t.prix >= :minPrice')
                     ->andWhere('t.prix <= :maxPrice')
                     ->setParameter('minPrice', $minPrice)
                     ->setParameter('maxPrice', $maxPrice);

        // Filtre par capacité
        if ($minCapacity !== null && $minCapacity !== '') {
            $queryBuilder->andWhere('t.capacite >= :minCapacity')
                         ->setParameter('minCapacity', $minCapacity);
        }

        // Filtre par type
        if ($type !== 'all') {
            $queryBuilder->andWhere('t.type = :type')
                         ->setParameter('type', $type);
        }

        // Exécution de la requête
        $transports = $queryBuilder->getQuery()->getResult();

        // Paramètres de filtre pour le template
        $filterParams = [
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'minCapacity' => $minCapacity,
            'type' => $type,
        ];

        return $this->render('transports/Transport_search.html.twig', [
            'transports' => $transports,
            'departure' => $departure,
            'filterParams' => $filterParams,
        ]);
    }


    private $httpClient;
    private $logger;
    private $mapboxApiKey;
    // Injection des dépendances via le constructeur
    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    #[Route('/check-distance', name: 'app_check_distance', methods: ['POST'])]
    public function checkDistance(Request $request, LoggerInterface $logger): JsonResponse
    {
        $departure = $request->request->get('departure');
        $destination = $request->request->get('destination');

        $logger->info('Checking distance: Departure = {departure}, Destination = {destination}', [
            'departure' => $departure,
            'destination' => $destination,
        ]);

        if (!$departure || !$destination) {
            $logger->warning('Missing departure or destination');
            return new JsonResponse([
                'success' => false,
                'message' => 'Départ ou destination manquant.'
            ], 400);
        }

        try {
            $distance = $this->calculateDistance($departure, $destination);
            $logger->info('Distance calculated: {distance} km', ['distance' => $distance]);
            return new JsonResponse([
                'success' => true,
                'distance' => $distance,
                'exceedsLimit' => $distance > 500
            ]);
        } catch (\Exception $e) {
            $logger->error('Distance calculation failed: {message}', ['message' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateDistance(string $departure, string $destination): float
    {
        $apiKey = $_ENV['OPENROUTESERVICE_API_KEY'] ?? null;
        if (!$apiKey) {
            throw new \RuntimeException('OpenRouteService API key is missing.');
        }

        $client = HttpClient::create();

        // Get coordinates for departure and destination
        $startCoords = $this->getCoordinates($departure);
        $endCoords = $this->getCoordinates($destination);

        // Request to OpenRouteService for distance
        $response = $client->request('GET', 'https://api.openrouteservice.org/v2/directions/driving-car', [
            'query' => [
                'api_key' => $apiKey,
                'start' => $startCoords,
                'end' => $endCoords,
            ],
        ]);

        $data = $response->toArray();
        return $data['features'][0]['properties']['segments'][0]['distance'] / 1000; // Convert to km
    }

    private function getCoordinates(string $location): string
    {
        $apiKey = $_ENV['MAPBOX_ACCESS_TOKEN'] ?? null;
        if (!$apiKey) {
            throw new \RuntimeException('Mapbox API key is missing.');
        }

        $client = HttpClient::create();
        try {
            $response = $client->request('GET', 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($location) . '.json', [
                'query' => [
                    'access_token' => $apiKey,
                    'types' => 'place,locality',
                    'limit' => 1,
                    'language' => 'fr'
                ],
            ]);

            $data = $response->toArray();
            if (!empty($data['features'])) {
                $coords = $data['features'][0]['center'];
                return "{$coords[0]},{$coords[1]}"; // [longitude, latitude]
            }
            throw new \RuntimeException('Could not geocode location: ' . $location . '. Please specify a more precise location (e.g., a city).');
        } catch (\Exception $e) {
            $this->logger->error('Mapbox API error for location {location}: {message}', [
                'location' => $location,
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not geocode location: ' . $location . '. Please specify a more precise location (e.g., a city).');
        }
    }



    #[Route('/cities/autocomplete', name: 'app_cities_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, CityAutocompleter $cityAutocompleter, LoggerInterface $logger): JsonResponse
    {
        $query = $request->query->get('q', '');
    
        try {
            // Récupérer les suggestions
            $cities = $cityAutocompleter->getCitySuggestions($query);
    
            // Formatter les résultats pour l'autocomplétion
            $suggestions = array_map(function ($city) {
                return [
                    'label' => sprintf('%s, %s', $city['name'], $city['country']),
                    'value' => $city['name'],
                    'coordinates' => $city['coordinates'],
                ];
            }, $cities);
    
            return new JsonResponse($suggestions);
        } catch (\Exception $e) {
            $logger->error('Autocomplete failed: {message}', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return new JsonResponse([], 200); // Return empty array to avoid client-side error
        }
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
    
    #[Route('/transports/affecter/{transportId}', name: 'client_transport_affect_trajet', methods: ['GET'])]
public function affectTrajet(int $transportId, EntityManagerInterface $entityManager): Response
{
    // Validate transport existence
    $transport = $entityManager->getRepository(Transport::class)->find($transportId);
    if (!$transport) {
        $this->addFlash('error', 'Transport non trouvé.');
        return $this->redirectToRoute('client_transports_list');
    }

    // Redirect to the new trajet selection page
    return $this->redirectToRoute('client_trajet_affect', ['transportId' => $transportId]);
}

#[Route('/transports/assigner/{transportId}/{trajetId}', name: 'client_transport_assign_trajet', methods: ['GET'])]
public function assignTrajet(int $transportId, int $trajetId, EntityManagerInterface $entityManager): Response
{
    // Validate transport
    $transport = $entityManager->getRepository(Transport::class)->find($transportId);
    if (!$transport) {
        $this->addFlash('error', 'Transport non trouvé.');
        return $this->redirectToRoute('client_transports_list');
    }

    // Validate trajet
    $trajet = $entityManager->getRepository(Trajet::class)->find($trajetId);
    if (!$trajet) {
        $this->addFlash('error', 'Trajet non trouvé.');
        return $this->redirectToRoute('client_transports_list');
    }

    // Assign trajet to transport
    try {
        $transport->setId_Trajet($trajet);
        $entityManager->flush();
        $this->addFlash('success', 'Trajet affecté avec succès au transport.');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur lors de l\'affectation du trajet : ' . $e->getMessage());
    }

    return $this->redirectToRoute('client_transports_list');
}

#[Route('/client/transport/{id}', name: 'client_transport_details')]
public function details(Transport $transport, EntityManagerInterface $entityManager): Response
{
    // Get associated trajet
    $trajet = $transport->getId_Trajet();

    // Get similar transports (same type, excluding the current transport)
    $autresTransports = $entityManager->getRepository(Transport::class)->findBy(
        [
            'type' => $transport->getType(),
            'id_user' => $transport->getId_User(),
        ],
        ['id' => 'DESC'],
        6
    );
    $autresTransports = array_filter($autresTransports, fn($t) => $t->getId() !== $transport->getId());

    return $this->render('transports/transport_details.html.twig', [
        'transport' => $transport,
        'trajet' => $trajet,
        'autresTransports' => $autresTransports,
    ]);
}
}