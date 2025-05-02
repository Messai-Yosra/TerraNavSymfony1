<?php

namespace App\Service;

use App\Entity\Chambre;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;

class ChambreStatsService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getAdvancedStats(): array
    {
        return [
            'predictions' => $this->getPredictions(),
            'performance' => $this->getPerformanceStats(),
            'heatmap' => $this->getRoomHeatmap(),
            'trends' => $this->getTrends(),
        ];
    }

    private function getPredictions(): array
    {
        // Simulation de prédictions (à remplacer par de vraies données)
        return [
            'peak_periods' => [
                [
                    'start_date' => (new \DateTime('+2 months'))->format('Y-m-d'),
                    'end_date' => (new \DateTime('+2 months +5 days'))->format('Y-m-d'),
                    'predicted_occupancy' => 95,
                    'suggested_price_increase' => 15
                ]
            ],
            'low_periods' => [
                [
                    'start_date' => (new \DateTime('+3 months'))->format('Y-m-d'),
                    'end_date' => (new \DateTime('+3 months +15 days'))->format('Y-m-d'),
                    'predicted_occupancy' => 45,
                    'suggested_discount' => 20
                ]
            ],
            'confidence_score' => 92
        ];
    }

    private function getPerformanceStats(): array
    {
        // Simulation de statistiques de performance (à adapter selon vos besoins)
        return [
            'monthly_goals' => [
                'customer_satisfaction' => 85,
                'room_turnover' => 92,
                'cleanliness_score' => 88,
                'revenue_target' => 95
            ],
            'top_performers' => [
                [
                    'name' => 'Sarah L.',
                    'score' => 98,
                    'badges' => ['speed', 'service', 'cleanliness'],
                    'achievements' => ['record_mensuel', 'excellence_service']
                ],
                [
                    'name' => 'Marc D.',
                    'score' => 95,
                    'badges' => ['efficiency', 'teamwork'],
                    'achievements' => ['performance_constante']
                ]
            ]
        ];
    }

    private function getRoomHeatmap(): array
    {
        $qb = $this->em->createQueryBuilder();
        
        // Cette requête devrait être adaptée à votre structure de base de données
        $result = $qb->select('c.numero, COUNT(r.id) as reservation_count')
            ->from(Chambre::class, 'c')
            ->leftJoin('c.reservations', 'r')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        $heatmapData = [];
        foreach ($result as $row) {
            $heatmapData[] = [
                'room' => $row['numero'],
                'floor' => substr($row['numero'], 0, 1),
                'popularity' => min(100, ($row['reservation_count'] * 10))
            ];
        }

        return $heatmapData;
    }

    private function getTrends(): array
    {
        // Simulation de tendances (à adapter avec vos données réelles)
        return [
            'occupancy_trend' => [
                'current' => 75,
                'previous' => 70,
                'change' => 5,
                'trend' => 'up'
            ],
            'revenue_trend' => [
                'current' => 15000,
                'previous' => 14000,
                'change' => 7.14,
                'trend' => 'up'
            ],
            'satisfaction_trend' => [
                'current' => 4.5,
                'previous' => 4.3,
                'change' => 4.65,
                'trend' => 'up'
            ]
        ];
    }
} 