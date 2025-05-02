<?php

namespace App\Repository\Voyage;

use App\Entity\Offre;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }
    public function findAllWithImages(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.pathImages IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
    public function findByFilters(array $criteria): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.id_offre', 'o')
            ->addSelect('o')
            ->andWhere('v.dateRetour >= CURRENT_DATE()') // Date de retour après aujourd'hui
            ->andWhere('v.nbPlacesD > 0');

        if (!empty($criteria['search'])) {
            $qb->andWhere('v.titre LIKE :search OR v.description LIKE :search')
                ->setParameter('search', '%'.$criteria['search'].'%');
        }

        if (!empty($criteria['minPrice'])) {
            $qb->andWhere('v.prix >= :minPrice')
                ->setParameter('minPrice', $criteria['minPrice']);
        }

        if (!empty($criteria['maxPrice'])) {
            $qb->andWhere('v.prix <= :maxPrice')
                ->setParameter('maxPrice', $criteria['maxPrice']);
        }

        if (!empty($criteria['minPlaces'])) {
            $qb->andWhere('v.nbPlacesD >= :minPlaces')
                ->setParameter('minPlaces', $criteria['minPlaces']);
        }

        if (!empty($criteria['type']) && $criteria['type'] !== 'all') {
            $qb->andWhere('v.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        if ($criteria['onSale']) {
            $qb->andWhere('o.reduction IS NOT NULL AND o.reduction > 0');
        }

        // Tri
        switch ($criteria['sort']) {
            case 'alpha':
                $qb->orderBy('v.titre', 'ASC');
                break;
            case 'prix_asc':
                $qb->orderBy('v.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('v.prix', 'DESC');
                break;
            case 'remise_desc':
                $qb->orderBy('o.reduction', 'DESC');
                break;
            default:
                $qb->orderBy('v.dateDepart', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
    public function findByFiltersAgence(array $filters = [])
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.id_user', 'u'); // Jointure avec l'utilisateur

        // Filtre par nom d'agence de l'utilisateur
        if (!empty($filters['nomAgence'])) {
            $qb->andWhere('u.nomagence = :nomAgence')
                ->setParameter('nomAgence', $filters['nomAgence']);
        }

        // Filtre par recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('v.titre LIKE :search')
                ->setParameter('search', '%'.$filters['search'].'%');
        }

        // Filtre par prix min
        if (isset($filters['minPrice'])) {
            $qb->andWhere('v.prix >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        // Filtre par prix max
        if (isset($filters['maxPrice'])) {
            $qb->andWhere('v.prix <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        // Filtre par nombre de places minimum
        if (isset($filters['minPlaces'])) {
            $qb->andWhere('v.nbPlacesD >= :minPlaces')
                ->setParameter('minPlaces', $filters['minPlaces']);
        }

        // Filtre par type de voyage
        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $qb->andWhere('v.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre pour les voyages en solde
        if (isset($filters['onSale'])) {
            $qb->join('v.id_offre', 'o')
                ->andWhere('o.titre != :noOffer')
                ->setParameter('noOffer', 'Aucun Offre');
        }

        // Tri des résultats
        switch ($filters['sort'] ?? '') {
            case 'alpha':
                $qb->orderBy('v.titre', 'ASC');
                break;
            case 'prix_asc':
                $qb->orderBy('v.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('v.prix', 'DESC');
                break;
            case 'remise_asc':
                $qb->leftJoin('v.id_offre', 'o')
                    ->orderBy('o.reduction', 'ASC');
                break;
            case 'remise_desc':
                $qb->leftJoin('v.id_offre', 'o')
                    ->orderBy('o.reduction', 'DESC');
                break;
            case 'plus_proche':
                $qb->orderBy('v.dateDepart', 'ASC');
                break;
            default:
                $qb->orderBy('v.dateDepart', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
    public function findWithDiscountInfo()
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.id_offre', 'o')
            ->addSelect('o')
            ->getQuery()
            ->getResult();
    }

    // src/Repository/VoyageRepository.php

    public function findSimilarVoyages(Voyage $voyage, int $maxResults = 3): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.destination = :destination')
            ->setParameter('destination', $voyage->getDestination())
            ->orderBy('v.dateDepart', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
    // Pour trouver les voyages associés à une offre
    public function findVoyagesByOffre(Offre $offre): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.id_offre = :offre')
            ->andWhere('v.dateDepart >= CURRENT_DATE()')
            ->setParameter('offre', $offre)
            ->orderBy('v.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTitlesStartingWith(string $query): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.titre')
            ->where('v.titre LIKE :query')
            ->setParameter('query', $query.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
    // Dans VoyageRepository.php

    public function findAllWithOffres(): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.id_offre', 'o')
            ->addSelect('o')
            ->orderBy('v.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getDestinationStats(): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.destination as name', 'COUNT(v.id) as count')
            ->groupBy('v.destination')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyVoyageCounts(): array
    {
        return $this->createQueryBuilder('v')
            ->select('SUBSTRING(v.dateDepart, 1, 7) as month', 'COUNT(v.id) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTripDurationStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT 
            DATEDIFF(v.dateRetour, v.dateDepart) as duration,
            COUNT(v.id) as count,
            AVG(v.prix) as averagePrice
        FROM voyage v
        GROUP BY duration
        ORDER BY duration ASC
    ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    public function getReservationHeatmapData(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT 
            v.destination,
            MONTH(v.dateDepart) as month,
            COUNT(v.id) as reservationCount,
            AVG(v.prix) as averagePrice
        FROM voyage v
        GROUP BY v.destination, month
        ORDER BY month, reservationCount DESC
    ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    // Dans VoyageRepository.php
    public function findExpiredVoyages(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.dateRetour < CURRENT_DATE()')
            ->orderBy('v.dateRetour', 'DESC')
            ->setMaxResults(5) // Limiter à 5 suggestions max
            ->getQuery()
            ->getResult();
    }

    public function findDestinationsAndTypesForAI(): array
    {
        return $this->createQueryBuilder('v')
            ->select('DISTINCT v.destination', 'v.type', 'v.prix')
            ->setMaxResults(100) // Limite pour éviter de surcharger
            ->getQuery()
            ->getResult();
    }
}