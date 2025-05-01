<?php

namespace App\Controller\Admin;

use App\Service\ExcelImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExcelImportController extends AbstractController
{
    #[Route('/admin/import/hebergements', name: 'admin_import_hebergements')]
    public function importHebergements(Request $request, ExcelImportService $excelImportService): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('excel_file');
            
            if ($file) {
                $result = $excelImportService->importHebergements($file);
                
                $this->addFlash(
                    'success',
                    sprintf('Import terminé : %d hébergements importés avec succès', $result['success'])
                );
                
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $this->addFlash('error', $error);
                    }
                }
                
                return $this->redirectToRoute('admin_import_hebergements');
            }
        }

        return $this->render('admin/import/hebergements.html.twig');
    }
} 