<?php

namespace App\Controller\voyages;

use App\Entity\Offre;
use App\Entity\Voyage;
use App\Repository\Utilisateur\UtilisateurRepository;
use App\Repository\Voyage\OffreRepository;
use App\Repository\Voyage\VoyageRepository;
use App\Service\OpenRouterService;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

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
        $voyages = $voyageRepository->findBy(['id_user' => $user]);

        $handle = fopen('php://memory', 'r+');

        // En-tête avec BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'ID', 'Titre', 'Destination', 'Date Départ', 'Date Retour',
            'Prix', 'Places', 'Type', 'Offre Associée'
        ], ';');

        foreach ($voyages as $voyage) {
            // Format des dates avec tabulation pour forcer le format texte
            $dateDepart = $voyage->getDateDepart() ? "\t" . $voyage->getDateDepart()->format('Y-m-d H:i:s') : '';
            $dateRetour = $voyage->getDateRetour() ? "\t" . $voyage->getDateRetour()->format('Y-m-d H:i:s') : '';

            fputcsv($handle, [
                $voyage->getId(),
                $voyage->getTitre(),
                $voyage->getDestination(),
                $dateDepart,
                $dateRetour,
                $voyage->getPrix(),
                $voyage->getNbPlacesD(),
                $voyage->getType(),
                $voyage->getId_offre() ? $voyage->getId_offre()->getTitre() : 'Aucune'
            ], ';');
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $filename = sprintf('liste-voyages-agence-%s-%s.csv',
            $user->getNomagence(),
            date('Y-m-d')
        );

        $response = new Response($csvContent);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

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
        $sheet->setCellValue('I1', 'ID Offre');
        $sheet->setCellValue('J1', 'Titre Offre');
        $sheet->setCellValue('K1', 'Réduction');
        $sheet->setCellValue('L1', 'Date Début Offre');
        $sheet->setCellValue('M1', 'Date Fin Offre');
        $sheet->setCellValue('N1', 'Description Offre');

        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D3D3D3'] // Gris clair
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM],
                'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
            ]
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Données
        $row = 2;
        foreach ($voyages as $voyage) {
            $offre = $voyage->getId_offre();

            $sheet->setCellValue('A'.$row, $voyage->getId());
            $sheet->setCellValue('B'.$row, $voyage->getTitre());
            $sheet->setCellValue('C'.$row, $voyage->getDestination());
            $sheet->setCellValue('D'.$row, $voyage->getDateDepart() ? $voyage->getDateDepart()->format('d/m/Y H:i') : '');
            $sheet->setCellValue('E'.$row, $voyage->getDateRetour() ? $voyage->getDateRetour()->format('d/m/Y H:i') : '');
            $sheet->setCellValue('F'.$row, $voyage->getPrix());
            $sheet->setCellValue('G'.$row, $voyage->getNbPlacesD());
            $sheet->setCellValue('H'.$row, $voyage->getType());

            // Informations de l'offre
            if ($offre) {
                $sheet->setCellValue('I'.$row, $offre->getId());
                $sheet->setCellValue('J'.$row, $offre->getTitre());
                $sheet->setCellValue('K'.$row, $offre->getReduction().'%');
                $sheet->setCellValue('L'.$row, $offre->getDateDebut() ? $offre->getDateDebut()->format('d/m/Y') : '');
                $sheet->setCellValue('M'.$row, $offre->getDateFin() ? $offre->getDateFin()->format('d/m/Y') : '');
                $sheet->setCellValue('N'.$row, $offre->getDescription());

                // Style pour les lignes avec offre
                $sheet->getStyle('I'.$row.':N'.$row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E6FFE6'); // Vert très clair
            } else {
                $sheet->setCellValue('I'.$row, 'Aucune');
                $sheet->mergeCells('I'.$row.':N'.$row);
                $sheet->getStyle('I'.$row)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            // Format conditionnel pour les prix élevés
            if ($voyage->getPrix() > 1000) {
                $sheet->getStyle('F'.$row)
                    ->getFont()->setBold(true)->getColor()->setRGB('FF0000');
            }

            // Alternance des couleurs de ligne pour meilleure lisibilité
            $rowStyle = $sheet->getStyle('A'.$row.':N'.$row);
            if ($row % 2 == 0) {
                $rowStyle->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5'); // Gris très clair
            }

            // Bordures pour toutes les cellules
            $rowStyle->getBorders()
                ->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
        }

        // Formater les colonnes de date
        $sheet->getStyle('D2:E'.$row)
            ->getNumberFormat()
            ->setFormatCode('dd/mm/yyyy hh:mm');

        $sheet->getStyle('L2:M'.$row)
            ->getNumberFormat()
            ->setFormatCode('dd/mm/yyyy');

        // Formater la colonne de prix
        $sheet->getStyle('F2:F'.$row)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00" DT"');

        // Auto-size columns
        foreach(range('A','N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Protection de la feuille (optionnel)
        $sheet->getProtection()->setSheet(true);
        $sheet->getStyle('A2:N'.$row)->getProtection()->setLocked(false);

        // Créer le fichier Excel
        $writer = new Xlsx($spreadsheet);

        // Générer le nom du fichier
        $nomAgence = $user->getNomagence() ?? 'Agence';
        $filename = sprintf('voyages-et-offres-%s-%s.xlsx', $nomAgence, date('Y-m-d'));

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
    #[Route('/agence/voyage/{id}/export-ics', name: 'app_export_voyage_ics')]
    public function exportVoyageToIcs(Voyage $voyage): Response
    {
        // Format des dates selon la norme iCalendar
        $dateStart = $voyage->getDateDepart()->format('Ymd\THis');
        $dateEnd = $voyage->getDateRetour()->format('Ymd\THis');

        // Construction du contenu .ics
        $icsContent = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Agence Voyage//FR
BEGIN:VEVENT
UID:{$voyage->getId()}@agence-voyage
DTSTAMP:{$dateStart}
DTSTART:{$dateStart}
DTEND:{$dateEnd}
SUMMARY:{$voyage->getTitre()}
DESCRIPTION:{$voyage->getDestination()}
LOCATION:{$voyage->getDestination()}
END:VEVENT
END:VCALENDAR
ICS;

        // Création de la réponse
        $response = new Response($icsContent);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="voyage-%s.ics"',
            $voyage->getId()
        ));

        return $response;
    }

    /*
     {# Bouton d'export ICS #}
                                    <a href="{{ path('app_export_voyage_ics', {'id': voyage.id}) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Ajouter à mon calendrier">
                                        <i class="bi bi-calendar-plus"></i> Exporter vers calendrier
                                    </a>
    */


    // Ajoutez ceci dans StatistiqueController.php



    #[Route('/agence/statistiques/generer-rapport', name: 'app_generer_rapport_agence')]
    public function genererRapport(
        Request $request,
        OffreRepository $offreRepository,
        VoyageRepository $voyageRepository,
        UtilisateurRepository $userRepository,
        OpenRouterService $openRouterService
    ): Response {
        $user = $userRepository->find(1); // À remplacer par l'utilisateur connecté

        // Récupérer les données
        $offres = $offreRepository->findOffresByAgence($user->getId());
        $voyages = $voyageRepository->findBy(['id_user' => $user]);

        // Générer les analyses avec l'IA
        $analyseVoyages = $this->genererAnalyseVoyages($voyages, $openRouterService);
        $analyseOffres = $this->genererAnalyseOffres($offres, $openRouterService);
        $recommandations = $this->genererRecommandations($voyages, $offres, $openRouterService);

        // Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML
        $html = $this->renderView('voyages/export/rapport_complet.html.twig', [
            'offres' => $offres,
            'voyages' => $voyages,
            'agence' => $user->getNomagence(),
            'date' => new \DateTime(),
            'analyseVoyages' => $analyseVoyages,
            'analyseOffres' => $analyseOffres,
            'recommandations' => $recommandations
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Générer le nom du fichier
        $filename = sprintf('rapport-complet-agence-%s-%s.pdf',
            $user->getNomagence(),
            date('Y-m-d')
        );

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
            ]
        );
    }

    private function genererAnalyseVoyages(array $voyages, OpenRouterService $ia): string
    {
        $data = [
            'total_voyages' => count($voyages),
            'destinations' => [],
            'prix_moyen' => 0,
            'types' => []
        ];

        $totalPrix = 0;
        foreach ($voyages as $voyage) {
            $totalPrix += $voyage->getPrix();

            // Compter les destinations
            if (!isset($data['destinations'][$voyage->getDestination()])) {
                $data['destinations'][$voyage->getDestination()] = 0;
            }
            $data['destinations'][$voyage->getDestination()]++;

            // Compter les types
            if (!isset($data['types'][$voyage->getType()])) {
                $data['types'][$voyage->getType()] = 0;
            }
            $data['types'][$voyage->getType()]++;
        }

        $data['prix_moyen'] = $totalPrix / max(1, count($voyages));

        $question = "En tant qu'expert en analyse de données touristiques, analysez ces statistiques sur les voyages d'une agence et fournissez une analyse concise en français (max 150 mots):\n\n".
            "Nombre total de voyages: {$data['total_voyages']}\n".
            "Destinations: ".implode(', ', array_keys($data['destinations']))."\n".
            "Prix moyen: ".number_format($data['prix_moyen'], 2)." DT\n".
            "Types de voyages: ".implode(', ', array_keys($data['types']))."\n\n".
            "Fournissez une analyse professionnelle avec des insights utiles.";

        return $ia->askQuestion($question);
    }

    private function genererAnalyseOffres(array $offres, OpenRouterService $ia): string
    {
        $data = [
            'total_offres' => count($offres),
            'reduction_moyenne' => 0,
            'offres_actives' => 0
        ];

        $totalReduction = 0;
        $now = new \DateTime();

        foreach ($offres as $offre) {
            $totalReduction += $offre->getReduction();

            if ($offre->getDateDebut() <= $now && $offre->getDateFin() >= $now) {
                $data['offres_actives']++;
            }
        }

        $data['reduction_moyenne'] = $totalReduction / max(1, count($offres));

        $question = "En tant qu'expert en marketing touristique, analysez ces statistiques sur les offres promotionnelles d'une agence et fournissez une analyse concise en français (max 150 mots):\n\n".
            "Nombre total d'offres: {$data['total_offres']}\n".
            "Réduction moyenne: ".number_format($data['reduction_moyenne'], 2)."%\n".
            "Offres actuellement actives: {$data['offres_actives']}\n\n".
            "Fournissez une analyse professionnelle avec des recommandations potentielles.";

        return $ia->askQuestion($question);
    }

    private function genererRecommandations(array $voyages, array $offres, OpenRouterService $ia): string
    {
        $stats = [
            'nb_voyages' => count($voyages),
            'nb_offres' => count($offres),
            'destinations' => [],
            'types' => []
        ];

        foreach ($voyages as $voyage) {
            if (!isset($stats['destinations'][$voyage->getDestination()])) {
                $stats['destinations'][$voyage->getDestination()] = 0;
            }
            $stats['destinations'][$voyage->getDestination()]++;

            if (!isset($stats['types'][$voyage->getType()])) {
                $stats['types'][$voyage->getType()] = 0;
            }
            $stats['types'][$voyage->getType()]++;
        }

        $question = "En tant que consultant expert en stratégie touristique, basé sur ces données, fournissez 3-5 recommandations concrètes et personnalisées en français pour améliorer la performance d'une agence de voyage (max 200 mots):\n\n".
            "Statistiques clés:\n".
            "- Nombre de voyages: {$stats['nb_voyages']}\n".
            "- Nombre d'offres: {$stats['nb_offres']}\n".
            "- Destinations principales: ".implode(', ', array_keys($stats['destinations']))."\n".
            "- Types de voyages: ".implode(', ', array_keys($stats['types']))."\n\n".
            "Les recommandations doivent être pratiques, réalisables et spécifiques au contexte.";

        return $ia->askQuestion($question);
    }
}