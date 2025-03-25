<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Utilisateur;
use App\Entity\Offre;
use App\Entity\Reservation;

#[ORM\Entity]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: "voyages")]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Offre $id_offre;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "voyages")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\Column(name: "pointDepart", type: "string", length: 50, nullable: true)]
    private ?string $pointDepart;

    #[ORM\Column(name: "dateDepart", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateDepart;

    #[ORM\Column(name: "dateRetour", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateRetour;

    #[ORM\Column(name: "destination", type: "string", length: 50, nullable: true)]
    private ?string $destination;

    #[ORM\Column(name: "nbPlacesD", type: "integer", nullable: true)]
    private ?int $nbPlacesD;

    #[ORM\Column(name: "type", type: "string", length: 50, nullable: true)]
    private ?string $type;

    #[ORM\Column(name: "prix", type: "float", nullable: true)]
    private ?float $prix;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description;

    #[ORM\Column(name: "titre", type: "string", length: 50, nullable: true)]
    private ?string $titre;

    #[ORM\Column(name: "pathImages", type: "text", nullable: true)]
    private ?string $pathImages;

    #[ORM\OneToMany(mappedBy: "id_voyage", targetEntity: Reservation::class)]
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

    public function getId_offre(): Offre
    {
        return $this->id_offre;
    }

    public function setId_offre(Offre $id_offre): self
    {
        $this->id_offre = $id_offre;
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

    public function getPointDepart(): ?string
    {
        return $this->pointDepart;
    }

    public function setPointDepart(?string $pointDepart): self
    {
        $this->pointDepart = $pointDepart;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(?\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateRetour(): ?\DateTimeInterface
    {
        return $this->dateRetour;
    }

    public function setDateRetour(?\DateTimeInterface $dateRetour): self
    {
        $this->dateRetour = $dateRetour;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function getNbPlacesD(): ?int
    {
        return $this->nbPlacesD;
    }

    public function setNbPlacesD(?int $nbPlacesD): self
    {
        $this->nbPlacesD = $nbPlacesD;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getPathImages(): ?string
    {
        return $this->pathImages;
    }

    public function setPathImages(?string $pathImages): self
    {
        $this->pathImages = $pathImages;
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
            $reservation->setId_voyage($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getId_voyage() === $this) {
                $reservation->setId_voyage(null);
            }
        }
        return $this;
    }
}