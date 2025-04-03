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
    public function findByFilters(array $filters = [])
    {
        $qb = $this->createQueryBuilder('v');

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
                $qb->leftJoin('v.id_offre', 'o') // Jointure optionnelle pour le tri par remise
                ->orderBy('o.reduction', 'ASC');
                break;
            case 'remise_desc':
                $qb->leftJoin('v.id_offre', 'o') // Jointure optionnelle pour le tri par remise
                ->orderBy('o.reduction', 'DESC');
                break;
            case 'plus_proche':
                $qb->orderBy('v.dateDepart', 'ASC'); // Tri par date la plus proche
                break;
            default:
                $qb->orderBy('v.dateDepart', 'DESC'); // Tri par défaut
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
}