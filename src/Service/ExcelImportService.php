<?php

namespace App\Service;

use App\Entity\Hebergement;
use App\Entity\Chambre;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ExcelImportService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function importHebergements(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $errors = [];
        $successCount = 0;

        // Commencer à la ligne 2 pour ignorer les en-têtes
        foreach ($worksheet->getRowIterator(2) as $row) {
            try {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $data = [];
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }

                // Vérifier si l'hébergement existe déjà
                $existingHebergement = $this->entityManager->getRepository(Hebergement::class)
                    ->findOneBy(['nom' => $data[0]]);

                if (!$existingHebergement) {
                    $hebergement = new Hebergement();
                    $hebergement->setNom($data[0]);
                    $hebergement->setDescription($data[1]);
                    $hebergement->setAdresse($data[2]);
                    $hebergement->setVille($data[3]);
                    $hebergement->setPays($data[4]);
                    $hebergement->setPrix($data[5]);

                    // Trouver le propriétaire
                    $proprietaire = $this->entityManager->getRepository(Utilisateur::class)
                        ->findOneBy(['email' => $data[6]]);

                    if ($proprietaire) {
                        $hebergement->setProprietaire($proprietaire);
                    } else {
                        throw new \Exception("Propriétaire non trouvé pour l'email: " . $data[6]);
                    }

                    $this->entityManager->persist($hebergement);

                    // Créer les chambres associées
                    $chambresData = explode(';', $data[7]);
                    foreach ($chambresData as $chambreData) {
                        $chambreInfo = explode(':', $chambreData);
                        if (count($chambreInfo) === 2) {
                            $chambre = new Chambre();
                            $chambre->setNumero($chambreInfo[0]);
                            $chambre->setType($chambreInfo[1]);
                            $chambre->setHebergement($hebergement);
                            $this->entityManager->persist($chambre);
                        }
                    }

                    $this->entityManager->flush();
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur à la ligne " . $row->getRowIndex() . ": " . $e->getMessage();
            }
        }

        return [
            'success' => $successCount,
            'errors' => $errors
        ];
    }
} 