<?php
// filepath: c:\Users\asus\TerraNavSymfony1\TerraNavSymfony1\src\Controller\utilisateurs\ReclamationClientController.php
namespace App\Controller\utilisateurs;

use App\Entity\Reclamation;
use App\Form\ReclamationClientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReclamationClientController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/reclamer', name: 'app_reclamer')]  // Simplified route
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer les réclamations existantes de l'utilisateur
        $reclamations = $this->entityManager->getRepository(Reclamation::class)
            ->findBy(['id_user' => $user], ['dateReclamation' => 'DESC']);
        
        // Créer une nouvelle réclamation
        $reclamation = new Reclamation();
        $reclamation->setId_user($user);
        $reclamation->setDateReclamation(new \DateTime());
        $reclamation->setEtat('Non traité'); // État par défaut
        
        $form = $this->createForm(ReclamationClientType::class, $reclamation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($reclamation);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Votre réclamation a été envoyée avec succès.');
            return $this->redirectToRoute('app_reclamer');
        }
        
        return $this->render('utilisateurs/reclamantionClient.html.twig', [
            'form' => $form->createView(),
            'reclamations' => $reclamations
        ]);
    }
}