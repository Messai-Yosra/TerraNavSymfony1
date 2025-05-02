<?php

namespace App\Controller\hebergements;

use App\Entity\Chambre;
use App\Entity\Hebergement;
use App\Service\ExcelImportService;
use App\Service\ChambreStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChambreAdminController extends AbstractController
{
    #[Route('/chambresAdmin', name: 'admin_chambres')]
    public function index(
        EntityManagerInterface $em, 
        Request $request, 
        ExcelImportService $excelImportService,
        ChambreStatsService $statsService
    ): Response
    {
        // Gérer l'import Excel si un fichier est soumis
        if ($request->isMethod('POST')) {
            $file = $request->files->get('excel_file');
            if ($file) {
                $result = $excelImportService->importHebergements($file);
                
                if ($result['success'] > 0) {
                    $this->addFlash('success', sprintf('%d hébergements et leurs chambres ont été importés avec succès', $result['success']));
                }
                
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $this->addFlash('error', $error);
                    }
                }
                
                return $this->redirectToRoute('admin_chambres');
            }
        }

        $chambres = $em->getRepository(Chambre::class)->findBy([], ['numero' => 'ASC']);
        $hebergements = $em->getRepository(Hebergement::class)->findBy([], ['nom' => 'ASC']);

        // Statistiques de base
        $basicStats = [
            'total' => count($chambres),
            'disponibles' => count(array_filter($chambres, fn($c) => $c->getDisponibilite())),
            'hebergements' => $em->createQueryBuilder()
                ->select('h.nom AS hebergement, COUNT(c.id) AS count')
                ->from(Chambre::class, 'c')
                ->join('c.id_hebergement', 'h')
                ->groupBy('h.id')
                ->orderBy('count', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),
            'capacites' => $em->createQueryBuilder()
                ->select('c.capacite AS capacite, COUNT(c.id) AS count')
                ->from(Chambre::class, 'c')
                ->where('c.capacite IS NOT NULL')
                ->groupBy('c.capacite')
                ->orderBy('c.capacite', 'ASC')
                ->getQuery()
                ->getResult(),
            'prix' => $em->createQueryBuilder()
                ->select('h.nom AS hebergement, AVG(c.prix) AS avg_prix')
                ->from(Chambre::class, 'c')
                ->join('c.id_hebergement', 'h')
                ->where('c.prix IS NOT NULL')
                ->groupBy('h.id')
                ->orderBy('avg_prix', 'DESC')
                ->getQuery()
                ->getResult(),
        ];

        // Fusionner les statistiques de base avec les statistiques avancées
        $stats = array_merge($basicStats, $statsService->getAdvancedStats());

        return $this->render('hebergements/chambre/ChambreAdmin.html.twig', [
            'chambres' => $chambres,
            'hebergements' => $hebergements,
            'stats' => $stats,
        ]);
    }
}