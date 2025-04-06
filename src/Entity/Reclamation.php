<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reclamations")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'utilisateur ne peut pas être vide")]
    private Utilisateur $id_user;

    #[ORM\Column(name: "dateReclamation", type: "datetime", nullable: true)]
    #[Assert\Type("\DateTimeInterface", message: "La date doit être valide")]
    private ?\DateTimeInterface $dateReclamation;

    #[ORM\Column(name: "description", type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description;

    #[ORM\Column(name: "sujet", type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le sujet ne peut pas être vide")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le sujet ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Choice(
        choices: ["Problème technique", "Question sur un voyage", "Problème de paiement", "Suggestion", "Autre"],
        message: "Le sujet sélectionné n'est pas valide"
    )]
    private ?string $sujet;

    #[ORM\Column(name: "etat", type: "string", length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["Non traité", "Traité"],
        message: "L'état doit être soit 'Non traité' soit 'Traité'"
    )]
    private ?string $etat;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
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

    public function getDateReclamation(): ?\DateTimeInterface
    {
        return $this->dateReclamation;
    }

    public function setDateReclamation(?\DateTimeInterface $dateReclamation): self
    {
        $this->dateReclamation = $dateReclamation;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(?string $sujet): self
    {
        $this->sujet = $sujet;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }
}