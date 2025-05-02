<?php

namespace App\Controller\Admin;

use App\Service\ExcelExportService;
use App\Entity\Hebergement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/export')]
class ExcelExportController extends AbstractController
{
    #[Route('/hebergements', name: 'admin_export_hebergements')]
    public function exportHebergements(EntityManagerInterface $entityManager, ExcelExportService $excelExportService): Response
    {
        // Récupérer tous les hébergements avec leurs chambres et propriétaires
        $hebergements = $entityManager->getRepository(Hebergement::class)
            ->createQueryBuilder('h')
            ->leftJoin('h.chambres', 'c')
            ->leftJoin('h.id_user', 'u')
            ->addSelect('c', 'u')
            ->getQuery()
            ->getResult();
        
        // Générer le fichier Excel en arrière-plan
        $excelExportService->generateExcel();
        
        // Afficher la prévisualisation avec les données
        return $this->render('admin/excel/preview.html.twig', [
            'hebergements' => $hebergements
        ]);
    }

    #[Route('/hebergements/download', name: 'admin_export_hebergements_download')]
    public function downloadHebergements(ExcelExportService $excelExportService): Response
    {
        return $excelExportService->downloadExcel();
    }
} 