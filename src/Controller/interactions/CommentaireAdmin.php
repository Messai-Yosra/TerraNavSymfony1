<?php
namespace App\Controller\interactions;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin/commentaires')]
#[IsGranted('ROLE_ADMIN')]
class CommentaireAdmin extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_commentaires_list')]
    public function list(): Response
    {
        $query = $this->entityManager->createQuery(
            'SELECT c, u, p 
             FROM App\Entity\Commentaire c
             LEFT JOIN c.id_user u
             LEFT JOIN c.id_post p'
        );
        $commentaires = $query->getResult();
        
        return $this->render('interactions/CommentaireAdmin.html.twig', [
            'commentaires' => $commentaires,
            'stats' => [
                'total' => count($commentaires),
                'last_week' => 0,
                'average_per_day' => 0
            ]
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $commentaire = $this->entityManager->getRepository(Commentaire::class)->find($id);
        
        if (!$commentaire) {
            $this->addFlash('error', 'Commentaire non trouvé');
            return $this->redirectToRoute('admin_commentaires_list');
        }

        if ($this->isCsrfTokenValid('delete'.$commentaire->getId(), $request->request->get('_token'))) {
            try {
                $post = $commentaire->getId_Post();
                if ($post) {
                    $post->setNbCommentaires($post->getNbCommentaires() - 1);
                    $this->entityManager->persist($post);
                }
                
                $this->entityManager->remove($commentaire);
                $this->entityManager->flush();
                $this->addFlash('success', 'Commentaire supprimé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du commentaire');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('admin_commentaires_list');
    }

    #[Route('/export', name: 'admin_commentaires_export')]
    public function export(): Response
    {
        $query = $this->entityManager->createQuery(
            'SELECT c, u, p 
             FROM App\Entity\Commentaire c
             LEFT JOIN c.id_user u
             LEFT JOIN c.id_post p'
        );
        $commentaires = $query->getResult();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ID')
              ->setCellValue('B1', 'Auteur')
              ->setCellValue('C1', 'Email')
              ->setCellValue('D1', 'Post ID')
              ->setCellValue('E1', 'Contenu')
              ->setCellValue('F1', 'Date');

        $row = 2;
        foreach ($commentaires as $commentaire) {
            $sheet->setCellValue('A'.$row, $commentaire->getId())
                  ->setCellValue('B'.$row, $commentaire->getId_User()->getUsername())
                  ->setCellValue('C'.$row, $commentaire->getId_User()->getEmail())
                  ->setCellValue('D'.$row, $commentaire->getId_Post()->getId())
                  ->setCellValue('E'.$row, $commentaire->getContenu())
                  ->setCellValue('F'.$row, $commentaire->getDate()->format('d/m/Y H:i'));
            $row++;
        }

        foreach(range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_commentaires_'.date('Y-m-d').'.xlsx"');
        
        return $response;
    }
}