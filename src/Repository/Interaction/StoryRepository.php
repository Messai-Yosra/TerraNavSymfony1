<?php

namespace App\Repository\Interaction;

use App\Entity\Story;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Story::class);
    }

    public function findActiveStories()
    {
        $dateLimit = new \DateTime('-24 hours');

        return $this->createQueryBuilder('s')
            ->where('s.created_at >= :dateLimit')
            ->andWhere('s.is_active = :active')
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('active', true)
            ->orderBy('s.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}