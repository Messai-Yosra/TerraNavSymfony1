<?php

namespace App\Controller\interactions;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

final class PublicationAdminController extends AbstractController
{
    #[Route('/PublicationsAdmin', name: 'admin_publications')]
    public function index(EntityManagerInterface $em): Response
    {
        $posts = $em->getRepository(Post::class)->findAll();
        
        usort($posts, function($a, $b) {
            if ($a->getStatut() === $b->getStatut()) {
                return $b->getDate() <=> $a->getDate();
            }
            return ($a->getStatut() === 'traitée') ? 1 : -1;
        });

        return $this->render('interactions/publicationAdmin.html.twig', [
            'posts' => $posts,
            'stats' => $this->calculateStats($posts)
        ]);
    }

    #[Route('/PublicationsAdmin/export-csv', name: 'admin_post_export_csv')]
public function exportExcel(EntityManagerInterface $em): Response
{
    $posts = $em->getRepository(Post::class)->findAll();
    usort($posts, fn($a, $b) => $b->getDate() <=> $a->getDate());

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Ajouter le logo
    $logoPath = $this->getParameter('kernel.project_dir') . '/public/img/TerraNav.png';
    if (file_exists($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('TerraNav Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(60); // Ajustez la hauteur selon vos besoins
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);

        // Décaler les données pour faire de la place au logo
        $sheet->insertNewRowBefore(1, 4);
    }

    // Titre du document
    $sheet->setCellValue('A5', 'Liste des Publications TerraNav');
    $sheet->mergeCells('A5:E5');
    $sheet->getStyle('A5')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => '4F81BD']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);

    // En-têtes (maintenant à la ligne 7)
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_GRADIENT_LINEAR,
            'startColor' => ['rgb' => '4F81BD'],
            'endColor' => ['rgb' => '4BACC6']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];

    $sheet->setCellValue('A7', 'ID')
          ->setCellValue('B7', 'Auteur')
          ->setCellValue('C7', 'Description')
          ->setCellValue('D7', 'Statut')
          ->setCellValue('E7', 'Date Publication');
    
    $sheet->getStyle('A7:E7')->applyFromArray($headerStyle);

    // Données (commencent à la ligne 8)
    $row = 8;
    foreach ($posts as $post) {
        $sheet->setCellValue('A'.$row, $post->getId())
              ->setCellValue('B'.$row, $post->getId_User()->getUsername())
              ->setCellValue('C'.$row, $post->getDescription())
              ->setCellValue('D'.$row, $post->getStatut())
              ->setCellValue('E'.$row, $post->getDate()->format('d/m/Y H:i'));

        // Style des lignes
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => ($row % 2) ? 'E6E6E6' : 'FFFFFF']
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9']
                ]
            ]
        ];
        $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($rowStyle);

        // Couleur conditionnelle statut
        $statusColor = $post->getStatut() === 'traitée' ? '2D8E36' : 'D91E18';
        $sheet->getStyle('D'.$row)
              ->getFont()->getColor()->setRGB($statusColor);

        $row++;
    }

    // Ajuster les dimensions des colonnes
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(40);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(20);

    // Date d'export
    $sheet->setCellValue('A6', 'Exporté le : ' . (new \DateTime())->format('d/m/Y H:i'));
    $sheet->mergeCells('A6:E6');
    $sheet->getStyle('A6')->applyFromArray([
        'font' => ['italic' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
    ]);

    // Export
    try {
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="posts_'.date('Y-m-d').'.xlsx"');
        
        return $response;

    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur d\'export : '.$e->getMessage());
        return $this->redirectToRoute('admin_publications');
    }
}

#[Route('/PublicationsAdmin/export-pdf', name: 'admin_post_export_pdf')]
public function exportPdf(EntityManagerInterface $em): Response
{
    $posts = $em->getRepository(Post::class)->findAll();
    usort($posts, fn($a, $b) => $b->getDate() <=> $a->getDate());

    // Configure Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    // Définir le répertoire racine pour les images
    $publicDirectory = $this->getParameter('kernel.project_dir') . '/public';
    $options->setChroot($publicDirectory);

    $dompdf = new Dompdf($options);

    // Convertir le chemin du logo en base64
    $logoPath = $publicDirectory . '/img/TerraNav.png';
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = 'data:image/png;base64,' . $logoData;

    // Générer le HTML
    $html = $this->renderView('interactions/pdf/posts_pdf.html.twig', [
        'posts' => $posts,
        'date_export' => new \DateTime(),
        'logo_src' => $logoSrc
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $response = new Response($dompdf->output());
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 
        'attachment;filename="TerraNav_publications_'.date('Y-m-d').'.pdf"'
    );

    return $response;
}
    #[Route('/PublicationsAdmin/post/{id}/traiter', name: 'admin_post_traiter')]
    public function traiter(int $id, EntityManagerInterface $em): Response
    {
        $post = $em->getRepository(Post::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException("Post introuvable.");
        }

        $post->setStatut('traitée');
        $em->flush();

        return $this->redirectToRoute('admin_publications');
    }

    #[Route('/PublicationsAdmin/post/{id}/delete', name: 'admin_post_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $post = $em->getRepository(Post::class)->find($id);
        if ($post) {
            $em->remove($post);
            $em->flush();
        }

        return $this->redirectToRoute('admin_publications');
    }

    private function calculateStats(array $posts): array
    {
        $traitees = array_filter($posts, fn($p) => $p->getStatut() === 'traitée');
        $nonTraitees = array_filter($posts, fn($p) => $p->getStatut() !== 'traitée');

        return [
            'total' => count($posts),
            'traitees' => count($traitees),
            'non_traitees' => count($nonTraitees),
            'pourcentage_traitees' => count($posts) ? round(count($traitees)/count($posts)*100) : 0
        ];
    }
}