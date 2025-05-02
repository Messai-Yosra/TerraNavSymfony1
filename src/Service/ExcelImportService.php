<?php

namespace App\Service;

use App\Entity\Hebergement;
use App\Entity\Chambre;
use App\Entity\Utilisateur;
use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

class ExcelImportService
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function importHebergements(UploadedFile $file, Utilisateur $user): array
    {
        $this->logger->info('Début de l\'import Excel');
        
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Ignorer la ligne d'en-tête
        array_shift($rows);
        
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => count($rows)
        ];

        $this->logger->info('Nombre de lignes à traiter: ' . count($rows));

        foreach ($rows as $index => $row) {
            try {
                if (empty($row[0])) {
                    $this->logger->warning('Ligne ' . ($index + 2) . ' ignorée car vide');
                    continue;
                }

                $this->logger->info('Traitement de la ligne ' . ($index + 2) . ' - Hébergement: ' . $row[0]);

                $hebergement = new Hebergement();
                $hebergement->setNom($row[0]);
                $hebergement->setDescription($row[1] ?? '');
                $hebergement->setAdresse($row[2] ?? '');
                $hebergement->setVille($row[3] ?? '');
                $hebergement->setPays($row[4] ?? '');
                $hebergement->setTypeHebergement($row[5] ?? 'Hôtel');
                $hebergement->setServices($row[6] ?? '');
                $hebergement->setPolitiqueAnnulation($row[7] ?? '');
                $hebergement->setContact($row[8] ?? '');
                $hebergement->setNoteMoyenne(0.0);
                $hebergement->setIdUser($user);
                $hebergement->setNbChambres(0);

                // Création des chambres si spécifiées
                if (!empty($row[9])) {
                    $this->logger->info('Traitement des chambres pour ' . $row[0]);
                    $chambresData = explode(';', $row[9]);
                    $nbChambres = 0;
                    
                    foreach ($chambresData as $chambreInfo) {
                        $chambreData = explode('|', $chambreInfo);
                        if (count($chambreData) >= 3) {
                            $this->logger->info('Création d\'une nouvelle chambre: ' . $chambreData[0]);
                            
                            $chambre = new Chambre();
                            $chambre->setNumero($chambreData[0]);
                            $chambre->setCapacite((int)$chambreData[1]);
                            $chambre->setPrix((float)$chambreData[2]);
                            if (isset($chambreData[3])) $chambre->setDescription($chambreData[3]);
                            if (isset($chambreData[4])) $chambre->setEquipements($chambreData[4]);
                            if (isset($chambreData[5])) $chambre->setVue($chambreData[5]);
                            if (isset($chambreData[6])) $chambre->setTaille((float)$chambreData[6]);
                            
                            // Gestion de l'URL 3D
                            if (isset($chambreData[7]) && !empty($chambreData[7])) {
                                $chambre->setUrl3d($chambreData[7]);
                            }
                            
                            // Gestion des images
                            if (isset($chambreData[8]) && !empty($chambreData[8])) {
                                $this->logger->info('Traitement des images pour la chambre ' . $chambreData[0]);
                                $images = explode(',', $chambreData[8]);
                                foreach ($images as $imageUrl) {
                                    $image = new Image();
                                    $image->setUrlImage('/ChambreImages/' . trim($imageUrl));
                                    $image->setIdChambre($chambre);
                                    $this->entityManager->persist($image);
                                    $this->logger->info('Image ajoutée: ' . $imageUrl);
                                }
                            } else {
                                // Ajouter l'image par défaut si aucune image n'est spécifiée
                                $image = new Image();
                                $image->setUrlImage('/ChambreImages/no-image-icon-4.png');
                                $image->setIdChambre($chambre);
                                $this->entityManager->persist($image);
                                $this->logger->info('Image par défaut ajoutée pour la chambre ' . $chambreData[0]);
                            }
                            
                            $chambre->setDisponibilite(true);
                            $chambre->setId_hebergement($hebergement);
                            $this->entityManager->persist($chambre);
                            
                            $nbChambres++;
                        }
                    }
                    $hebergement->setNbChambres($nbChambres);
                    $this->logger->info('Nombre total de chambres pour ' . $row[0] . ': ' . $nbChambres);
                }

                $this->entityManager->persist($hebergement);
                $results['success']++;
                $this->logger->info('Hébergement ' . $row[0] . ' créé avec succès');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du traitement de la ligne ' . ($index + 2) . ': ' . $e->getMessage());
                $results['errors'][] = "Ligne " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        if ($results['success'] > 0) {
            try {
                $this->entityManager->flush();
                $this->logger->info('Tous les changements ont été enregistrés en base de données');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'enregistrement final: ' . $e->getMessage());
                $results['errors'][] = "Erreur lors de l'enregistrement: " . $e->getMessage();
            }
        }

        return $results;
    }
} 