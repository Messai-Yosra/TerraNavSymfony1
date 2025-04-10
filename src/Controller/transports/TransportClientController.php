<?php
namespace App\Controller\transports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TransportClientController extends AbstractController
{
    #[Route('/transport', name: 'app_transports')]
    public function index(): Response
    {
        return $this->render('transports/transportClient.html.twig');
    }

    #[Route('/transport/search', name: 'app_transport_search')]
    public function search(Request $request): Response
    {
        // Récupérer les paramètres de recherche
        $departure = $request->query->get('departure');
        $destination = $request->query->get('destination');
        $date = $request->query->get('date');
        $passengers = $request->query->get('passengers');

        // Ici vous devrez implémenter la logique de recherche réelle
        // Pour l'instant, nous allons simplement retourner les résultats fictifs
        
        return $this->render('transports/transportSearchResults.html.twig', [
            'departure' => $departure,
            'destination' => $destination,
            'date' => $date,
            'passengers' => $passengers,
            // Vous ajouterez ici les résultats réels de votre recherche
        ]);
    }
}