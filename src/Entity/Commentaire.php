<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Post;
use App\Entity\Utilisateur;

#[ORM\Entity]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(name: 'id_post', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Post $id_post;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $date;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $contenu;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId_post(): Post
    {
        return $this->id_post;
    }

    public function setId_post(Post $id_post): self
    {
        $this->id_post = $id_post;
        return $this;
    }

    public function getId_user(): Utilisateur
    {
        return $this->id_user;
    }

    public function setId_user(Utilisateur $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }
}