<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Entity\Reaction;
#[ORM\Entity]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "posts")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\Column(type: "text", options: ["default" => "non traitée"])]
    private string $statut = "non traitée";

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $date;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\NotBlank(message: "L'image doit être remplie.")]
    private ?string $image;

    #[ORM\Column(name: "nbCommentaires", type: "integer", nullable: true)]
    private ?int $nbCommentaires;

    #[ORM\Column(name: "nbReactions", type: "integer", nullable: true)]
    private ?int $nbReactions;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\NotBlank(message: "La description du post ne doit pas être vide.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description du post doit contenir au moins {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/[a-zA-Z]/",
        message: "La description du post doit contenir au moins une lettre."
    )]
    private ?string $description;

    #[ORM\OneToMany(mappedBy: "id_post", targetEntity: Commentaire::class)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: "id_post", targetEntity: Reaction::class)]
    private Collection $reactions;

    public function __construct()
{
    $this->date = new \DateTime(); 
    $this->statut = "non traitée"; 
    $this->nbCommentaires = 0; 
    $this->nbReactions = 0; 
    $this->commentaires = new ArrayCollection();
    $this->reactions = new ArrayCollection();
}


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

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getNbCommentaires(): ?int
    {
        return $this->nbCommentaires;
    }

    public function setNbCommentaires(?int $nbCommentaires): self
    {
        $this->nbCommentaires = $nbCommentaires;
        return $this;
    }

    public function getNbReactions(): ?int
    {
        return $this->nbReactions;
    }

    public function setNbReactions(?int $nbReactions): self
    {
        $this->nbReactions = $nbReactions;
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

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setId_post($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getId_post() === $this) {
                $commentaire->setId_post(null);
            }
        }
        return $this;
    }

    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(Reaction $reaction): self
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions[] = $reaction;
            $reaction->setId_post($this);
        }
        return $this;
    }

    public function removeReaction(Reaction $reaction): self
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getId_post() === $this) {
                $reaction->setId_post(null);
            }
        }
        return $this;
    }
}