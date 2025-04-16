<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Trajet;
use App\Entity\Reservation;
use App\Entity\Utilisateur;

#[ORM\Entity]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "transports")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\ManyToOne(targetEntity: Trajet::class, inversedBy: "transports")]
    #[ORM\JoinColumn(name: 'id_trajet', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Trajet $id_trajet;

    #[ORM\Column(name: "nom", type: "string", length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\-]+$/",
        message: "Le nom ne peut contenir que des lettres, chiffres, espaces ou tirets"
    )]
    private ?string $nom;

    #[ORM\Column(name: "type", type: "string", length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Le type ne peut pas être vide")]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le type ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Choice(
        choices: ["Voiture privée", "Taxi", "Bus"],
        message: "Type de transport invalide"
    )]
    private ?string $type;

    #[ORM\Column(name: "capacite", type: "integer", nullable: true)]
    #[Assert\NotBlank(message: "La capacité est requise")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: "La capacité doit être entre {{ min }} et {{ max }}"
    )]
    private ?int $capacite;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description;

    #[ORM\Column(name: "contact", type: "string", length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Le contact est requis")]
    #[Assert\Length(
        min: 5,
        max: 20,
        minMessage: "Le contact doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le contact ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|[\+]?[0-9\s\-()]{5,20})$/",
        message: "Veuillez entrer un email ou numéro de téléphone valide"
    )]
    private ?string $contact;

    #[ORM\Column(name: "prix", type: "float")]
    #[Assert\NotNull(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif")]
    #[Assert\Range(
        min: 0.01,
        max: 10000,
        notInRangeMessage: "Le prix doit être entre {{ min }}DTN et {{ max }}DTN"
    )]
    private float $prix;

    #[ORM\Column(name: "imagePath", type: "string", length: 255, nullable: true)]
    private ?string $imagePath;

    #[ORM\OneToMany(mappedBy: "id_Transport", targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
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

    public function getId_trajet(): Trajet
    {
        return $this->id_trajet;
    }

    public function setId_trajet(Trajet $id_trajet): self
    {
        $this->id_trajet = $id_trajet;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): self
    {
        $this->capacite = $capacite;
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

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setId_Transport($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getId_Transport() === $this) {
                $reservation->setId_Transport(null);
            }
        }
        return $this;
    }
}