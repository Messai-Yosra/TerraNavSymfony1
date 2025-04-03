<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class StatistiqueController extends AbstractController
{
    #[Route('/agence/statistiques', name: 'app_statistiques_agence')]
    public function index(): Response
    {
        return $this->render('voyages/statistiques.html.twig');
    }

    #[Route('/agence/statistiques/offres/pdf', name: 'app_export_offres_pdf')]
    public function exportOffresPdf(OffreRepository $offreRepository, UtilisateurRepository $userRepository): Response
    {
        $user = $userRepository->find(1);
        // Récupérer les offres de l'agence connectée
        $offres = $offreRepository->findOffresByAgence($user->getId());

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML
        $html = $this->renderView('voyages/export/offres_pdf.html.twig', [
            'offres' => $offres,
            'agence' => $user->getNomagence(),
            'date' => new \DateTime()
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Générer le nom du fichier
        $filename = sprintf('statistiques-offres-agence-%s-%s.pdf',
            $user->getNomagence(),
            date('Y-m-d')
        );

        // Retourner la réponse PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }

    #[Route('/agence/statistiques/voyages/csv', name: 'app_export_voyages_csv')]
    public function exportVoyagesCsv(VoyageRepository $voyageRepository, UtilisateurRepository $userRepository): Response
    {
        $user = $userRepository->find(1);
        // Récupérer les voyages de l'agence connectée
        $voyages = $voyageRepository->findBy(['id_user' => $user]);

        // Préparer les données CSV
        $data = [];
        $data[] = ['ID', 'Titre', 'Destination', 'Date Départ', 'Date Retour', 'Prix', 'Places', 'Type', 'Offre Associée'];

        foreach ($voyages as $voyage) {
            $data[] = [
                $voyage->getId(),
                $voyage->getTitre(),
                $voyage->getDestination(),
                $voyage->getDateDepart() ? $voyage->getDateDepart()->format('Y-m-d H:i') : '',
                $voyage->getDateRetour() ? $voyage->getDateRetour()->format('Y-m-d H:i') : '',
                $voyage->getPrix(),
                $voyage->getNbPlacesD(),
                $voyage->getType(),
                $voyage->getId_offre() ? $voyage->getId_offre()->getTitre() : 'Aucune'
            ];
        }

        // Générer le contenu CSV
        $csvContent = '';
        foreach ($data as $row) {
            $csvContent .= implode(';', $row) . "\r\n";
        }

        // Générer le nom du fichier
        $filename = sprintf('liste-voyages-agence-%s-%s.csv',
            $user->getNomagence(),
            date('Y-m-d')
        );

        // Retourner la réponse CSV
        $response = new Response($csvContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }

    #[Route('/agence/statistiques/voyages/excel', name: 'app_export_voyages_excel')]
    public function exportVoyagesExcel(VoyageRepository $voyageRepository, UtilisateurRepository $userRepository): Response
    {
        $user = $userRepository->find(1);
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $voyages = $voyageRepository->findBy(['id_user' => $user]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-têtes
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Titre');
        $sheet->setCellValue('C1', 'Destination');
        $sheet->setCellValue('D1', 'Date Départ');
        $sheet->setCellValue('E1', 'Date Retour');
        $sheet->setCellValue('F1', 'Prix (DT)');
        $sheet->setCellValue('G1', 'Places');
        $sheet->setCellValue('H1', 'Type');
        $sheet->setCellValue('I1', 'Offre Associée');
        $sheet->setCellValue('J1', 'Réduction');

        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'] // Noir
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Données
        $row = 2;
        foreach ($voyages as $voyage) {
            $sheet->setCellValue('A'.$row, $voyage->getId());
            $sheet->setCellValue('B'.$row, $voyage->getTitre());
            $sheet->setCellValue('C'.$row, $voyage->getDestination());
            $sheet->setCellValue('D'.$row, $voyage->getDateDepart() ? $voyage->getDateDepart()->format('d/m/Y H:i') : '');
            $sheet->setCellValue('E'.$row, $voyage->getDateRetour() ? $voyage->getDateRetour()->format('d/m/Y H:i') : '');
            $sheet->setCellValue('F'.$row, $voyage->getPrix());
            $sheet->setCellValue('G'.$row, $voyage->getNbPlacesD());
            $sheet->setCellValue('H'.$row, $voyage->getType());
            $sheet->setCellValue('I'.$row, $voyage->getId_offre() ? $voyage->getId_offre()->getTitre() : 'Aucune');
            $sheet->setCellValue('J'.$row, $voyage->getId_offre() ? $voyage->getId_offre()->getReduction().'%' : '0%');

            // Format conditionnel pour les prix - CORRECTION ICI
            if ($voyage->getPrix() > 1000) {
                $style = $sheet->getStyle('F'.$row);
                $style->getFont()->setBold(true);

                // Méthode correcte pour définir la couleur
                $style->getFont()->getColor()->setRGB('FF0000'); // Rouge
            }

            $row++;
        }

        // Auto-size columns
        foreach(range('A','J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Créer le fichier Excel
        $writer = new Xlsx($spreadsheet);

        // Générer le nom du fichier
        $nomAgence = $user->getNomagence() ?? 'Agence';
        $filename = sprintf('voyages-%s-%s.xlsx', $nomAgence, date('Y-m-d'));

        // Créer une réponse temporaire
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        // Retourner la réponse
        $response = new Response(file_get_contents($tempFile));
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        unlink($tempFile);

        return $response;
    }
}