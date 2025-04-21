<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Interaction\ReactionRepository;
use App\Entity\Post;
use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
#[ORM\Table(name: 'reactions')]
#[ORM\UniqueConstraint(name: 'unique_user_post', columns: ['id_user_id', 'id_post_id'])]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $id_user = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $id_post = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?Utilisateur
    {
        return $this->id_user;
    }

    public function setIdUser(?Utilisateur $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getIdPost(): ?Post
    {
        return $this->id_post;
    }

    public function setIdPost(?Post $id_post): self
    {
        $this->id_post = $id_post;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
}