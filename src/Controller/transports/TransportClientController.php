<?php

namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Validator\Constraints\DistanceConstraint;
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
        ]);
    }
    #[Route('/transports/liste', name: 'client_transports_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchTerm = $request->query->get('search', '');
        $isAjax = $request->isXmlHttpRequest();

        $queryBuilder = $entityManager->getRepository(Transport::class)
            ->createQueryBuilder('t')
            ->where('t.id_user = :user')
            ->andWhere('t.id IS NOT NULL')
            ->andWhere('t.id > 0')
            ->setParameter('user', $entityManager->getReference(Utilisateur::class, 251));

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



    #[Route('/transports/search', name: 'app_transport_search')]
public function search(Request $request, EntityManagerInterface $entityManager): Response
{
    // Récupération des paramètres
    $departure = $request->query->get('departure');
    $passengers = $request->query->get('passengers', 1); // Valeur par défaut: 1 passager

    // Recherche dans la base de données
    $transports = $entityManager->getRepository(Transport::class)
        ->createQueryBuilder('t')
        ->join('t.id_trajet', 'tr')
        ->where('tr.pointDepart LIKE :departure')
        ->andWhere('t.capacite >= :passengers') // Filtre par capacité
        ->setParameter('departure', '%'.$departure.'%')
        ->setParameter('passengers', $passengers)
        ->getQuery()
        ->getResult();

    return $this->render('transports/Transport_search.html.twig', [
        'transports' => $transports,
        'departure' => $departure,
        'passengers' => $passengers
    ]);
}
#[Route('/transports/check-distance', name: 'app_check_distance', methods: ['POST'])]
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

    #[Route('/api/cities/autocomplete', name: 'app_cities_autocomplete', methods: ['GET'])]
    public function autocompleteCities(Request $request, CityAutocompleter $cityAutocompleter): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $cityAutocompleter->getCitySuggestions($query);
        
        return $this->json($suggestions);
    }


    #[Route('/transports/exporter/pdf', name: 'client_transports_export_pdf', methods: ['GET'])]
public function exportPdf(EntityManagerInterface $entityManager, Pdf $knpSnappyPdf): Response
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

    $filename = 'transports_list_' . date('Ymd_His') . '.pdf';

    return new Response(
        $knpSnappyPdf->getOutputFromHtml($html, [
            'encoding' => 'utf-8',
            'margin-top' => 10,
            'margin-bottom' => 10,
            'margin-left' => 10,
            'margin-right' => 10,
        ]),
        Response::HTTP_OK,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            ),
        ]
    );
}
}