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
    public function findFilteredOffres(array $filters = [])
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.titre IS NOT NULL')
            ->andWhere('o.dateFin >= :now OR o.dateFin IS NULL')
            ->setParameter('now', new \DateTime());

        // Filtres
        $this->applyFilters($qb, $filters);

        // Tri
        $this->applySorting($qb, $filters['sort'] ?? null);

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
            ->select('o.titre')
            ->where('o.titre LIKE :query')
            ->setParameter('query', $query.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

}

