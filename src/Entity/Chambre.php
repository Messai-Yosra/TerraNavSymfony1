<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Hebergement;
use App\Entity\Reservation;
use App\Entity\Image;

#[ORM\Entity]
class Chambre
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: "chambres")]
    #[ORM\JoinColumn(name: 'id_hebergement', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'hébergement associé ne peut pas être vide")]
    private Hebergement $id_hebergement;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le numéro de chambre ne peut pas être vide")]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le numéro ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $numero;

    #[ORM\Column(type: "boolean", nullable: true)]
    #[Assert\Type(type: "bool", message: "La disponibilité doit être un booléen")]
    private ?bool $disponibilite = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Assert\Type(type: "float", message: "Le prix doit être un nombre décimal")]
    #[Assert\PositiveOrZero(message: "Le prix doit être positif ou zéro")]
    private ?float $prix = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Type(type: "string", message: "La description doit être une chaîne de caractères")]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Type(type: "integer", message: "La capacité doit être un entier")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    private ?int $capacite = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Type(type: "string", message: "Les équipements doivent être une chaîne de caractères")]
    #[Assert\Length(
        max: 500,
        maxMessage: "Les équipements ne peuvent pas dépasser {{ limit }} caractères"
    )]
    private ?string $equipements = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Type(type: "string", message: "La vue doit être une chaîne de caractères")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La vue ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $vue = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Assert\Type(type: "float", message: "La taille doit être un nombre décimal")]
    #[Assert\Positive(message: "La taille doit être un nombre positif")]
    private ?float $taille = null;

    #[ORM\Column(name: "url_3d", type: "string", length: 2000, nullable: true)]
    #[Assert\Type(type: "string", message: "L'URL doit être une chaîne de caractères")]
    #[Assert\Url(message: "L'URL 3D doit être une URL valide")]
    #[Assert\Length(
        max: 2000,
        maxMessage: "L'URL ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $url_3d = null;

    #[ORM\OneToMany(mappedBy: "id_chambre", targetEntity: Image::class)]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: "id_Chambre", targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct()
    {
        $this->images = new ArrayCollection();
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

    public function getId_hebergement(): Hebergement
    {
        return $this->id_hebergement;
    }

    public function setId_hebergement(Hebergement $id_hebergement): self
    {
        $this->id_hebergement = $id_hebergement;
        return $this;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?bool $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;
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

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): self
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getEquipements(): ?string
    {
        return $this->equipements;
    }

    public function setEquipements(?string $equipements): self
    {
        $this->equipements = $equipements;
        return $this;
    }

    public function getVue(): ?string
    {
        return $this->vue;
    }

    public function setVue(?string $vue): self
    {
        $this->vue = $vue;
        return $this;
    }

    public function getTaille(): ?float
    {
        return $this->taille;
    }

    public function setTaille(?float $taille): self
    {
        $this->taille = $taille;
        return $this;
    }

    public function getUrl_3d(): ?string
    {
        return $this->url_3d;
    }

    public function setUrl_3d(?string $url_3d): self
    {
        $this->url_3d = $url_3d;
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setId_chambre($this);
        }
        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            if ($image->getId_chambre() === $this) {
                $image->setId_chambre(null);
            }
        }
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
            $reservation->setId_Chambre($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getId_Chambre() === $this) {
                $reservation->setId_Chambre(null);
            }
        }
        return $this;
    }

    public function getHebergement(): Hebergement
    {
        return $this->id_hebergement;
    }

    public function setHebergement(Hebergement $id_hebergement): self
    {
        $this->id_hebergement = $id_hebergement;
        return $this;
    }

    public function getUrl3d(): ?string
    {
        return $this->url_3d;
    }

    public function setUrl3d(?string $url_3d): self
    {
        $this->url_3d = $url_3d;
        return $this;
    }
}
