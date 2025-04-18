<?php

namespace App\Repository\Reservation;

use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 *
 * @method Panier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Panier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Panier[]    findAll()
 * @method Panier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    /**
     * Creates and persists a new panier
     */
    public function create(Panier $panier): void
    {
        $this->getEntityManager()->persist($panier);
        $this->getEntityManager()->flush();
    }

    /**
     * Updates an existing panier
     */
    public function update(Panier $panier): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Removes a panier by ID
     */
    public function delete(int $id): void
    {
        $panier = $this->find($id);
        if ($panier) {
            $this->getEntityManager()->remove($panier);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds panier by user ID
     */
    public function findByUser(int $userId): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id_user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Creates a panier for a user if they don't have one
     */
    public function createPanierForUser(int $userId): Panier
    {
        $panier = $this->findByUser($userId);

        if (!$panier) {
            $panier = new Panier();
            $panier->setIdUser($userId);
            $panier->setPrixTotal(0.0);
            $this->create($panier);
        }

        return $panier;
    }

    /**
     * Updates the total price of a panier (sum of all PENDING reservations)
     */
    public function updateTotalPrice(int $panierId): void
    {
        // First get the sum of all PENDING reservations
        $total = $this->createQueryBuilder('p')
            ->select('SUM(r.prix) as total')
            ->leftJoin('p.reservations', 'r')
            ->where('p.id = :panierId')
            ->andWhere('r.Etat = :status')
            ->setParameter('panierId', $panierId)
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();

        // Then update the panier's prix_total
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.prix_total', ':total')
            ->where('p.id = :panierId')
            ->setParameter('total', (float)$total)
            ->setParameter('panierId', $panierId)
            ->getQuery()
            ->execute();
    }

    /**
     * Finds all paniers with optional sorting
     * @param array $orderBy [field => direction]
     */
    public function findAllPaniers(array $orderBy = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('p.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds paniers with optional filtering and pagination
     * @param array $criteria [field => value]
     * @param array $orderBy [field => direction]
     * @param int|null $limit
     * @param int|null $offset
     */
    public function findPaniers(
        array $criteria = [],
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("p.$field = :$field")
                ->setParameter($field, $value);
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('p.'.$field, $direction);
            }
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Validates a panier by setting the validation date and resetting the total price to 0
     */
    public function validatePanier(int $panierId): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.date_validation', ':now')
            ->set('p.prix_total', 0)
            ->where('p.id = :panierId')
            ->setParameter('now', new \DateTime())
            ->setParameter('panierId', $panierId)
            ->getQuery()
            ->execute();
    }

}