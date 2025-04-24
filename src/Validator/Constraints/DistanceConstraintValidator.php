<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Exception\ClientException;
use Psr\Log\LoggerInterface;

class DistanceConstraintValidator extends ConstraintValidator
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validate($value, Constraint $constraint)
    {
        $form = $this->context->getRoot();
        $departure = $form->get('departure')->getData();
        $destination = $form->get('destination')->getData();

        $this->logger->info('DistanceConstraintValidator: Départ = {departure}, Destination = {destination}', [
            'departure' => $departure,
            'destination' => $destination,
        ]);

        if (!$departure || !$destination) {
            $this->logger->warning('Départ ou destination manquant.');
            return;
        }

        try {
            $distance = $this->calculateDistance($departure, $destination);
            $this->logger->info('Distance calculée: {distance} km', ['distance' => $distance]);

            if ($distance > 500) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ departure }}', $departure)
                    ->setParameter('{{ destination }}', $destination)
                    ->addViolation();
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul de la distance: {message}', ['message' => $e->getMessage()]);
            $this->context->buildViolation('Impossible de calculer la distance. Veuillez vérifier les lieux saisis ou réessayer plus tard.')
                ->addViolation();
        }
    }

    private function calculateDistance(string $departure, string $destination): float
    {
        $apiKey = $_ENV['OPENROUTESERVICE_API_KEY'] ?? null;
        if (!$apiKey) {
            throw new \RuntimeException('OpenRouteService API key is missing. Please configure OPENROUTESERVICE_API_KEY in .env.');
        }

        $client = HttpClient::create();

        // Récupérer les coordonnées des lieux
        $startCoords = $this->getCoordinates($departure);
        $endCoords = $this->getCoordinates($destination);

        $this->logger->info('Coordonnées: Départ = {startCoords}, Destination = {endCoords}', [
            'startCoords' => $startCoords,
            'endCoords' => $endCoords,
        ]);

        // Requête à OpenRouteService pour la distance
        try {
            $response = $client->request('GET', 'https://api.openrouteservice.org/v2/directions/driving-car', [
                'query' => [
                    'api_key' => $apiKey,
                    'start' => $startCoords,
                    'end' => $endCoords,
                ],
            ]);

            $data = $response->toArray();
            return $data['features'][0]['properties']['segments'][0]['distance'] / 1000; // Convertir en km
        } catch (ClientException $e) {
            throw new \RuntimeException('OpenRouteService API request failed: ' . $e->getMessage());
        }
    }

    private function getCoordinates(string $location): string
    {
        $client = HttpClient::create();
        try {
            $response = $client->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $location,
                    'format' => 'json',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => 'TerraNavSymfony/1.0',
                ],
            ]);

            $data = $response->toArray();
            if (!empty($data)) {
                return "{$data[0]['lon']},{$data[0]['lat']}";
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Nominatim API request failed: ' . $e->getMessage());
        }

        throw new \RuntimeException('Could not geocode location: ' . $location);
    }
}