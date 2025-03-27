<?php

namespace App\Repository\Reservation;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Creates and persists a new reservation
     */
    public function create(Reservation $reservation): void
    {
//        $reservation->setdate_reservation(new \DateTime());
//        $reservation->setDateAffectation(new \DateTime());

        $this->getEntityManager()->persist($reservation);
        $this->getEntityManager()->flush();
    }

    /**
     * Updates an existing reservation
     */
    public function update(Reservation $reservation): void
    {
        $reservation->setDateAffectation(new \DateTime());
        $this->getEntityManager()->flush();
    }

    /**
     * Removes a reservation by ID
     */
    public function delete(int $id): void
    {
        $reservation = $this->find($id);
        if ($reservation) {
            $this->getEntityManager()->remove($reservation);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds all reservations with optional sorting
     * @param array $orderBy [field => direction]
     */
    public function findAllReservations(array $orderBy = null): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds reservations by panier ID
     */
    public function findByPanier(int $panierId, array $orderBy = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.id_panier = :panierId')
            ->setParameter('panierId', $panierId);

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds reservations by type
     */
    public function findByType(string $type, array $orderBy = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.type_service = :type')
            ->setParameter('type', $type);

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Updates reservation status
     * @param int $id Reservation ID
     * @param string $status Status string ('PENDING', 'CONFIRMED', 'CANCELLED')
     */
    public function updateStatus(int $id, string $status): void
    {
        $validStatuses = ['PENDING', 'CONFIRMED'];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status value");
        }

        $this->createQueryBuilder('r')
            ->update()
            ->set('r.Etat', ':status')
            ->where('r.id = :id')
            ->setParameter('status', $status)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Counts reservations by type
     */
    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.type_service = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Finds reservations with optional filtering and pagination
     * @param array $criteria [field => value]
     * @param array $orderBy [field => direction]
     * @param int|null $limit
     * @param int|null $offset
     */
    public function findReservations(
        array $criteria = [],
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('r');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("r.$field = :$field")
                ->setParameter($field, $value);
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
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

    public function findByTypeAndStatus(string $type, string $status, array $orderBy = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.type_service = :type')
            ->andWhere('r.Etat = :status')
            ->setParameter('type', $type)
            ->setParameter('status', $status);

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('r.'.$field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }
}