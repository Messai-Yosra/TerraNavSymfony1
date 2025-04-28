<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Entity\Voyage;
use App\Form\voyages\VoyageType;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\OffreRepository;
use App\Service\OpenRouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VoyageController extends AbstractController
{
    #[Route('/AjoutVoyage', name: 'app_ajout_voyage', methods: ['GET', 'POST'])]
    public function ajoutVoyage(Request $request, OffreRepository $offreRepository, UtilisateurRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur
            $user = $userRepository->find(1);
            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_voyage');
            }

            $voyage->setId_user($user);

            // Gestion des images
            $uploadedFiles = $request->files->get('images');
            $imagePaths = [];

            if ($uploadedFiles) {
                $projectDir = $this->getParameter('kernel.project_dir');
                $uploadDir = $projectDir.'/public/img/voyages/';

                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                        $absolutePath = $uploadDir.$newFilename;

                        try {
                            $uploadedFile->move($uploadDir, $newFilename);
                            // Stocker le chemin absolu Windows
                            $windowsPath = str_replace('/', '\\', $absolutePath);
                            $imagePaths[] = $windowsPath;
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Erreur lors de l\'upload d\'image: '.$e->getMessage());
                        }
                    }
                }
            }

            if (empty($imagePaths)) {
                $defaultPath = str_replace(
                    '/',
                    '\\',
                    $this->getParameter('kernel.project_dir').'/public/img/about-1.jpg'
                );
                $imagePaths[] = $defaultPath;
            }

            $voyage->setPathImages(implode('***', $imagePaths));

            // Gestion de l'offre
            $idOffre = $request->request->get('id_offre');
            if ($idOffre) {
                $offre = $offreRepository->find($idOffre);
                $voyage->setId_offre($offre);
            }

            // Stockage temporaire en session
            $session = $request->getSession();
            $session->set('voyage_temporaire', $voyage);

            return $this->redirectToRoute('app_confirmation_ajout');
        }

        $offres = $offreRepository->findAll();
        return $this->render('voyages/AjouterVoyage.html.twig', [
            'form' => $form->createView(),
            'offres' => $offres
        ]);
    }

    #[Route('/confirmationAjout', name: 'app_confirmation_ajout')]
    public function confirmationAjout(Request $request): Response
    {
        $session = $request->getSession();
        $voyage = $session->get('voyage_temporaire');

        if (!$voyage) {
            $this->addFlash('error', 'Aucun voyage à confirmer');
            return $this->redirectToRoute('app_ajout_voyage');
        }

        // Séparer les images pour l'affichage
        $images = explode('***', $voyage->getPathImages());
        $voyage->setImageList($images);

        return $this->render('voyages/ConfirmerAjoutVoyage.html.twig', [
            'voyage' => $voyage
        ]);
    }

    #[Route('/publierVoyage', name: 'app_publier_voyage', methods: ['POST'])]
    public function publierVoyage(Request $request, EntityManagerInterface $entityManager, OffreRepository $offreRepository, UtilisateurRepository $userRepository): Response
    {
        $session = $request->getSession();
        $voyageData = $session->get('voyage_temporaire');

        if (!$voyageData) {
            $this->addFlash('error', 'Aucun voyage à publier');
            return $this->redirectToRoute('app_ajout_voyage');
        }

        try {
            // Récupérer l'utilisateur existant (id=1)
            $user = $userRepository->find(1);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur non trouvé');
                return $this->redirectToRoute('app_ajout_voyage');
            }

            // Créez une nouvelle instance de Voyage
            $voyage = new Voyage();
            $voyage->setId_user($user);
            $voyage->setTitre($voyageData->getTitre());
            $voyage->setDestination($voyageData->getDestination());
            $voyage->setPointDepart($voyageData->getPointDepart());
            $voyage->setDateDepart($voyageData->getDateDepart());
            $voyage->setDateRetour($voyageData->getDateRetour());
            $voyage->setType($voyageData->getType());
            $voyage->setNbPlacesD($voyageData->getNbPlacesD());
            $voyage->setPrix($voyageData->getPrix());
            $voyage->setDescription($voyageData->getDescription());
            $voyage->setPathImages($voyageData->getPathImages());

            // Gestion de l'offre
            if ($voyageData->getId_offre()) {
                // Récupère l'offre depuis la base de données
                $offre = $offreRepository->find($voyageData->getId_offre()->getId());

                if ($offre) {
                    $voyage->setId_offre($offre);
                } else {
                    $this->addFlash('warning', 'L\'offre associée n\'existe pas');
                }
            }

            $entityManager->persist($voyage);
            $entityManager->flush();

            $session->remove('voyage_temporaire');

            $this->addFlash('success', 'Le voyage a été publié avec succès !');
            return $this->redirectToRoute('app_voyages_agence');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : '.$e->getMessage());
            return $this->redirectToRoute('app_confirmation_ajout');
        }
    }


    #[Route('/generate-description', name: 'app_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request, OpenRouterService $openRouter): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $prompt = $this->createPromptFromData($data);
            $description = $openRouter->askQuestion($prompt);

            return $this->json([
                'success' => true,
                'description' => $description
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createPromptFromData(array $data): string
    {
        $prompt = "Tu es un expert en rédaction de descriptions de voyages touristiques. 
               Crée une description attrayante pour un voyage avec ces caractéristiques:\n\n";

        $prompt .= "- Destination: ".($data['destination'] ?? 'Non spécifié')."\n";
        $prompt .= "- Départ de: ".($data['pointDepart'] ?? 'Non spécifié')."\n";
        $prompt .= "- Type de voyage: ".($data['type'] ?? 'Non spécifié')."\n";
        $prompt .= "- Dates: Du ".($data['dateDepart'] ?? 'Non spécifié')." au ".($data['dateRetour'] ?? 'Non spécifié')."\n";
        $prompt .= "- Nombre de places: ".($data['nbPlacesD'] ?? 'Non spécifié')."\n";
        $prompt .= "- Prix: ".($data['prix'] ?? 'Non spécifié')."€\n\n";

        $prompt .= "La description doit être en français, entre 100 et 150 mots, avec un ton enthousiaste et professionnel. 
               Mets en valeur les points forts du voyage et utilise éventuellement des emojis pertinents.";

        return $prompt;
    }

    #[Route('/AjoutVoyageIA', name: 'app_ajout_voyage_ia')]
    public function ajoutVoyageIA(Request $request, OffreRepository $offreRepository): Response
    {
        $voyage = new Voyage();

        // Pré-remplir avec les paramètres de l'URL
        $voyage->setTitre($request->query->get('titre', 'Nouveau Voyage'));
        $voyage->setDestination($request->query->get('destination'));
        $voyage->setPointDepart($request->query->get('pointDepart'));
        $voyage->setType($request->query->get('type'));
        $voyage->setPrix($request->query->get('prix'));
        $voyage->setNbPlacesD($request->query->get('nbPlacesD'));
        $voyage->setDescription($request->query->get('description'));

        // Gestion des dates
        if ($dateDepart = $request->query->get('dateDepart')) {
            $voyage->setDateDepart(new \DateTime($dateDepart));
        }
        if ($dateRetour = $request->query->get('dateRetour')) {
            $voyage->setDateRetour(new \DateTime($dateRetour));
        }

        $form = $this->createForm(VoyageType::class, $voyage);

        $offres = $offreRepository->findAll();
        return $this->render('voyages/AjouterVoyageIA.html.twig', [
            'form' => $form->createView(),
            'offres' => $offres,
            'isIaGenerated' => true
        ]);
    }

    #[Route('/get-airports/{city}', name: 'app_get_airports', methods: ['GET'])]
    public function getAirports(string $city, OpenRouterService $openRouter): JsonResponse
    {
        try {
            // Formulation plus précise de la question avec inclusion du nom de la ville
            $question = "Liste tous les aéroports principaux de la ville de $city avec leur code IATA. 
                Réponds strictement sous ce format: 
                'Ville NomAéroport (CODE)-Ville NomAéroport (CODE)-...' 
                sans commentaires supplémentaires. 
                Le nom de la ville doit précéder chaque nom d'aéroport.
                Exemple pour Paris: 
                'Paris Aéroport Charles de Gaulle (CDG)-Paris Aéroport Orly (ORY)-Paris Aéroport Beauvais (BVA)'
                Exemple pour New York:
                'New York Aéroport John F. Kennedy (JFK)-New York Aéroport LaGuardia (LGA)-New York Aéroport Newark (EWR)'";

            $response = $openRouter->askQuestion($question);

            // Nettoyage et validation de la réponse
            $response = trim($response);

            // Supprimer les guillemets si présents
            $response = trim($response, '"\'');

            // Vérifier que la réponse contient bien des aéroports
            if (empty($response) || !str_contains($response, '(')) {
                throw new \Exception("Format de réponse inattendu de l'API");
            }

            // Séparation et nettoyage
            $airports = explode('-', $response);
            $airports = array_map('trim', $airports);
            $airports = array_filter($airports, function($item) {
                return !empty($item) && str_contains($item, '(');
            });

            // Si aucun aéroport valide trouvé
            if (empty($airports)) {
                return $this->json([
                    'success' => false,
                    'error' => "Aucun aéroport trouvé pour cette ville"
                ], 404);
            }

            return $this->json([
                'success' => true,
                'airports' => array_values($airports)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }











}