<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
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

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: "voyages", cascade: ["persist"])] // Ajoutez cascade: ["persist"]
    #[ORM\JoinColumn(name: 'id_offre', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Offre $id_offre = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "voyages", cascade: ["persist"])] // Ajoutez cascade persist
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\Column(name: "pointDepart", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le point de départ est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le point de départ doit faire au moins {{ limit }} caractères",
        maxMessage: "Le point de départ ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $pointDepart;

    #[ORM\Column(name: "dateDepart", type: "datetime")]
    #[Assert\NotBlank(message: "La date de départ est obligatoire")]
    #[Assert\GreaterThan("today", message: "La date de départ doit être dans le futur")]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: "dateRetour", type: "datetime")]
    #[Assert\NotBlank(message: "La date de retour est obligatoire")]
    #[Assert\Expression(
        "this.getDateRetour() > this.getDateDepart()",
        message: "La date de retour doit être après la date de départ"
    )]
    private ?\DateTimeInterface $dateRetour = null;

    #[ORM\Column(name: "destination", type: "string", length: 50)]
    #[Assert\NotBlank(message: "La destination est obligatoire")]
    #[Assert\Length(min: 3, max: 50)]
    private string $destination;

    #[ORM\Column(name: "nbPlacesD", type: "integer")]
    #[Assert\NotBlank(message: "Le nombre de places est obligatoire")]
    #[Assert\Positive(message: "Le nombre de places doit être positif")]
    private int $nbPlacesD;

    #[ORM\Column(name: "type", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le type de voyage est obligatoire")]
    #[Assert\Choice(choices: ["Avion", "Train", "Bateau"], message: "Choisissez un type valide")]
    private string $type;

    #[ORM\Column(name: "prix", type: "float")]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private float $prix;

    #[ORM\Column(name: "description", type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(min: 20, minMessage: "La description doit faire au moins {{ limit }} caractères")]
    private string $description;

    #[ORM\Column(name: "titre", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 5, max: 50)]
    private string $titre;

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

    public function getId_offre(): ?Offre
    {
        return $this->id_offre;
    }

    public function setId_offre(?Offre $id_offre): self
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

    private ?array $imageList = null;

    public function getImageList(): ?array
    {
        return $this->imageList;
    }

    public function setImageList(array $imageList): self
    {
        $this->imageList = $imageList;
        return $this;
    }

    public function getNomOffre(): ?string
    {
        return $this->id_offre ? $this->id_offre->getTitre() : null;
    }

    public function transformImagePaths(string $absolutePaths): string
    {
        return str_replace(
            [
                'C:\terra\TerraNavSymfony1\public\\',
                'C:/TerraNavSymfony1/public/',
                '\\',
                '***'
            ],
            [
                '',
                '',
                '/',
                '***'
            ],
            $absolutePaths
        );
    }

}