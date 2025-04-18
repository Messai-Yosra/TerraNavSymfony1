<?php
namespace App\Repository\Voyage;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }
    public function findAllActiveOffres() {
        $now = new \DateTime();
        return $this->createQueryBuilder('o')
            ->where('o.titre IS NOT NULL')
            ->andWhere('o.dateFin >= :now')
            ->setParameter('now', $now)
            ->orderBy('o.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findFilteredOffres(array $criteria): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.reduction > 0');

        if (!empty($criteria['search'])) {
            $qb->andWhere('o.titre LIKE :search')
                ->setParameter('search', '%'.$criteria['search'].'%');
        }

        if (!empty($criteria['minReduction'])) {
            $qb->andWhere('o.reduction >= :minReduction')
                ->setParameter('minReduction', $criteria['minReduction']);
        }

        if (!empty($criteria['dateDebut'])) {
            $qb->andWhere('o.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($criteria['dateDebut']));
        }

        if (!empty($criteria['dateFin'])) {
            $qb->andWhere('o.dateFin <= :dateFin')
                ->setParameter('dateFin', new \DateTime($criteria['dateFin']));
        }

        // Tri
        switch ($criteria['sort'] ?? '') {
            case 'alpha':
                $qb->orderBy('o.titre', 'ASC');
                break;
            case 'reduction_asc':
                $qb->orderBy('o.reduction', 'ASC');
                break;
            case 'reduction_desc':
                $qb->orderBy('o.reduction', 'DESC');
                break;
            default:
                $qb->orderBy('o.dateDebut', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    private function applyFilters($qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('o.titre LIKE :search')
                ->setParameter('search', '%'.$filters['search'].'%');
        }

        if (!empty($filters['minReduction'])) {
            $qb->andWhere('o.reduction >= :minReduction')
                ->setParameter('minReduction', (float)$filters['minReduction']);
        }

        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('o.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($filters['dateDebut']));
        }

        if (!empty($filters['dateFin'])) {
            $qb->andWhere('o.dateFin <= :dateFin')
                ->setParameter('dateFin', new \DateTime($filters['dateFin']));
        }
    }

    private function applySorting($qb, ?string $sortType): void
    {
        switch ($sortType) {
            case 'alpha':
                $qb->orderBy('o.titre', 'ASC');
                break;
            case 'reduction_asc':
                $qb->orderBy('o.reduction', 'ASC');
                break;
            case 'reduction_desc':
                $qb->orderBy('o.reduction', 'DESC');
                break;
            default:
                $qb->orderBy('o.dateDebut', 'DESC');
        }
    }

    public function findFilteredOffresAgence(array $filters = [])
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.id_user', 'u');

        // Filtre par nom d'agence de l'utilisateur
        if (!empty($filters['nomAgence'])) {
            $qb->andWhere('u.nomagence = :nomAgence')
                ->setParameter('nomAgence', $filters['nomAgence']);
        }

        // Filtre par recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('o.titre LIKE :search')
                ->setParameter('search', '%'.$filters['search'].'%');
        }

        // Filtre par réduction minimum
        if (!empty($filters['minReduction'])) {
            $qb->andWhere('o.reduction >= :minReduction')
                ->setParameter('minReduction', (float)$filters['minReduction']);
        }

        // Filtre par date de début
        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('o.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($filters['dateDebut']));
        }

        // Filtre par date de fin
        if (!empty($filters['dateFin'])) {
            $qb->andWhere('o.dateFin <= :dateFin')
                ->setParameter('dateFin', new \DateTime($filters['dateFin']));
        }

        // Tri des résultats
        switch ($filters['sort'] ?? '') {
            case 'alpha':
                $qb->orderBy('o.titre', 'ASC');
                break;
            case 'reduction_asc':
                $qb->orderBy('o.reduction', 'ASC');
                break;
            case 'reduction_desc':
                $qb->orderBy('o.reduction', 'DESC');
                break;
            default:
                $qb->orderBy('o.dateDebut', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    // Pour trouver les meilleures offres (plus grandes réductions)
    public function findMeilleuresOffres(int $maxResults = 6, ?Offre $currentOffre = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.dateFin >= CURRENT_DATE()')
            ->orderBy('o.reduction', 'DESC')
            ->setMaxResults($maxResults);

        if ($currentOffre) {
            $qb->andWhere('o.id != :currentId')
                ->setParameter('currentId', $currentOffre->getId());
        }

        return $qb->getQuery()->getResult();
    }



    public function findOffresByAgence(int $idAgence): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.id_user = :idAgence')
            ->setParameter('idAgence', $idAgence)
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findTitlesStartingWith(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.titre', 'o.reduction')
            ->where('LOWER(o.titre) LIKE LOWER(:query)')
            ->setParameter('query', $query.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getReductionDistribution(): array
    {
        $results = $this->createQueryBuilder('o')
            ->select('o.reduction', 'COUNT(o.id) as count')
            ->groupBy('o.reduction')
            ->getQuery()
            ->getResult();

        // Formatage des résultats en plages
        $ranges = [
            '0-10%' => 0,
            '11-20%' => 0,
            '21-30%' => 0,
            '30%+' => 0
        ];

        foreach ($results as $result) {
            $reduction = $result['reduction'];
            if ($reduction <= 10) {
                $ranges['0-10%'] += $result['count'];
            } elseif ($reduction <= 20) {
                $ranges['11-20%'] += $result['count'];
            } elseif ($reduction <= 30) {
                $ranges['21-30%'] += $result['count'];
            } else {
                $ranges['30%+'] += $result['count'];
            }
        }

        // Conversion en format attendu par le frontend
        $formatted = [];
        foreach ($ranges as $range => $count) {
            $formatted[] = ['range' => $range, 'count' => $count];
        }

        return $formatted;
    }



    // Ajoutez cette méthode pour obtenir les statistiques de statut
    public function getOfferStatusStats(): array
    {
        $now = new \DateTime();

        $active = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.dateDebut <= :now AND o.dateFin >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        $upcoming = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.dateDebut > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        $expired = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.dateFin < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            ['status' => 'active', 'count' => (int)$active],
            ['status' => 'upcoming', 'count' => (int)$upcoming],
            ['status' => 'expired', 'count' => (int)$expired]
        ];
    }



}

