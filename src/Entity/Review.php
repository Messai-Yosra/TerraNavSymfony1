<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;

#[ORM\Entity]
class Review
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reviews")]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_utilisateur;

    #[ORM\Column(name: "note", type: "integer", nullable: true)]
    private ?int $note;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): void
    {
        $this->id = $value;
    }

    public function getIdUtilisateur(): Utilisateur
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(Utilisateur $value): void
    {
        $this->id_utilisateur = $value;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $value): void
    {
        $this->note = $value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $value): void
    {
        $this->description = $value;
    }
}