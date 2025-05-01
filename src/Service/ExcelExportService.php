<?php

namespace App\Service;

use App\Entity\Hebergement;
use App\Entity\Chambre;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExcelExportService
{
    private $entityManager;
    private $projectDir;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    public function generateExcel(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $sheet->setCellValue('A1', 'Nom de l\'hébergement');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Adresse');
        $sheet->setCellValue('D1', 'Ville');
        $sheet->setCellValue('E1', 'Pays');
        $sheet->setCellValue('F1', 'Prix moyen');
        $sheet->setCellValue('G1', 'Email du propriétaire');
        $sheet->setCellValue('H1', 'Chambres');

        // Style des en-têtes
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Récupérer les hébergements
        $hebergements = $this->entityManager->getRepository(Hebergement::class)
            ->createQueryBuilder('h')
            ->leftJoin('h.chambres', 'c')
            ->leftJoin('h.id_user', 'u')
            ->addSelect('c', 'u')
            ->getQuery()
            ->getResult();

        $row = 2;

        foreach ($hebergements as $hebergement) {
            $sheet->setCellValue('A' . $row, $hebergement->getNom());
            $sheet->setCellValue('B' . $row, $hebergement->getDescription());
            $sheet->setCellValue('C' . $row, $hebergement->getAdresse());
            $sheet->setCellValue('D' . $row, $hebergement->getVille());
            $sheet->setCellValue('E' . $row, $hebergement->getPays());
            
            // Calcul de la moyenne des prix des chambres
            $chambres = $hebergement->getChambres();
            $prixMoyen = '';
            if (!$chambres->isEmpty()) {
                $prixTotal = 0;
                $nombreChambresAvecPrix = 0;
                
                foreach ($chambres as $chambre) {
                    $prix = $chambre->getPrix();
                    if ($prix !== null) {
                        $prixTotal += $prix;
                        $nombreChambresAvecPrix++;
                    }
                }
                
                if ($nombreChambresAvecPrix > 0) {
                    $prixMoyen = round($prixTotal / $nombreChambresAvecPrix, 2);
                }
            }
            $sheet->setCellValue('F' . $row, $prixMoyen);
            
            $sheet->setCellValue('G' . $row, $hebergement->getIdUser() ? $hebergement->getIdUser()->getEmail() : '');

            // Format des chambres
            $chambresList = [];
            foreach ($hebergement->getChambres() as $chambre) {
                $chambreInfo = [
                    'numero' => $chambre->getNumero(),
                    'capacite' => $chambre->getCapacite() ?? 0,
                    'prix' => $chambre->getPrix() ?? 0,
                    'taille' => $chambre->getTaille() ?? 0,
                    'vue' => $chambre->getVue() ?? 'Non spécifiée'
                ];
                $chambresList[] = sprintf(
                    'Chambre %s (Capacité: %d pers., Prix: %s TND, Taille: %s m², Vue: %s)',
                    $chambreInfo['numero'],
                    $chambreInfo['capacite'],
                    number_format($chambreInfo['prix'], 2, ',', ' '),
                    number_format($chambreInfo['taille'], 1, ',', ' '),
                    $chambreInfo['vue']
                );
            }
            $sheet->setCellValue('H' . $row, implode("\n", $chambresList));

            $row++;
        }

        // Ajuster la largeur des colonnes
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sauvegarder le fichier
        $publicDir = $this->projectDir . '/public/exports';
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0777, true);
        }

        $filePath = $publicDir . '/hebergements.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return 'exports/hebergements.xlsx';
    }

    public function downloadExcel(): BinaryFileResponse
    {
        $filePath = $this->projectDir . '/public/exports/hebergements.xlsx';
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'hebergements.xlsx'
        );
        return $response;
    }
} 