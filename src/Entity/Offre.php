<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Utilisateur;
use App\Entity\Voyage;

#[ORM\Entity]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "offres")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'utilisateur associé est obligatoire")]
    private Utilisateur $id_user;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le titre de l'offre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $titre;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 20,
        minMessage: "La description doit faire au moins {{ limit }} caractères"
    )]
    private string $description ;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "La réduction est obligatoire")]
    #[Assert\Type(
        type: "float",
        message: "La réduction doit être un nombre valide"
    )]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "La réduction doit être entre {{ min }}% et {{ max }}%"
    )]
    private ?float $reduction = null; // Rend la propriété nullable

    #[ORM\Column(name: "dateDebut", type: "datetime")]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de début doit être aujourd'hui ou dans le futur"
    )]
    private ?\DateTimeInterface $dateDebut = null; // Initialisation à null

    #[ORM\Column(name: "dateFin", type: "datetime")]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\Expression(
        "this.getDateFin() !== null && this.getDateDebut() !== null && this.getDateFin() > this.getDateDebut()",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null; // Initialisation à null

    #[ORM\Column(name: "imagePath", type: "string", length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\OneToMany(mappedBy: "id_offre", targetEntity: Voyage::class)]
    private Collection $voyages;

    public function __construct()
    {
        $this->voyages = new ArrayCollection();
    }

    // Les getters et setters restent identiques à ce que vous aviez déjà
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

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getReduction(): ?float
    {
        return $this->reduction;
    }

    public function setReduction(?float $reduction): self
    {
        $this->reduction = $reduction;
        return $this;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
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

    public function getVoyages(): Collection
    {
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): self
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages[] = $voyage;
            $voyage->setId_offre($this);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): self
    {
        if ($this->voyages->removeElement($voyage)) {
            if ($voyage->getId_offre() === $this) {
                $voyage->setId_offre(null);
            }
        }
        return $this;
    }

    public function transformSingleImagePath(string $absolutePath): string
    {
        return str_replace(
            [
                'C:\Users\asus\TerraNavSymfony1\TerraNavSymfony1\public\\',
                'C:\terra\TerraNavSymfony1\public\\',
                'C:/TerraNavSymfony1/public/',
                '\\'
            ],
            [
                '',
                '',
                '',
                '/'
            ],
            $absolutePath
        );
    }
}