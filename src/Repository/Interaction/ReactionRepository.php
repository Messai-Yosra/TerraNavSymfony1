<?php

namespace App\Repository\Interaction;
use App\Entity\Reaction;
use App\Entity\Utilisateur;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    public function findByUserAndPost(Utilisateur $user, Post $post): ?Reaction
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.id_user = :user')
            ->andWhere('r.id_post = :post')
            ->setParameter('user', $user)
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();
    }
}