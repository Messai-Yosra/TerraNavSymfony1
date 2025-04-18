<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Utilisateur;
use App\Entity\Chambre;

#[ORM\Entity]
class Hebergement
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "hebergements")]
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id", nullable: true)]
    private ?Utilisateur $id_user = null;

    #[ORM\Column(name: "nom", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le nom de l'hébergement ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $nom;

    #[ORM\Column(name: "description", type: "text")]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères"
    )]
    private string $description;

    #[ORM\Column(name: "adresse", type: "string", length: 255)]
    #[Assert\NotBlank(message: "L'adresse ne peut pas être vide")]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $adresse;

    #[ORM\Column(name: "ville", type: "string", length: 100)]
    #[Assert\NotBlank(message: "La ville ne peut pas être vide")]
    #[Assert\Length(
        max: 100,
        maxMessage: "La ville ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $ville;

    #[ORM\Column(name: "pays", type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le pays ne peut pas être vide")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le pays ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $pays;

    #[ORM\Column(name: "note_moyenne", type: "float")]
    #[Assert\NotBlank(message: "La note moyenne ne peut pas être vide")]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: "La note doit être comprise entre {{ min }} et {{ max }}"
    )]
    private float $note_moyenne;

    #[ORM\Column(name: "services", type: "text")]
    #[Assert\NotBlank(message: "Les services ne peuvent pas être vides")]
    private string $services;

    #[ORM\Column(name: "politique_annulation", type: "text")]
    #[Assert\NotBlank(message: "La politique d'annulation ne peut pas être vide")]
    private string $politique_annulation;

    #[ORM\Column(name: "contact", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le contact ne peut pas être vide")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le contact ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $contact;

    #[ORM\Column(name: "type_hebergement", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le type d'hébergement ne peut pas être vide")]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le type d'hébergement ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $type_hebergement;

    #[ORM\Column(name: "nb_chambres", type: "integer")]
    #[Assert\NotBlank(message: "Le nombre de chambres ne peut pas être vide")]
    #[Assert\PositiveOrZero(message: "Le nombre de chambres doit être positif ou zéro")]
    private int $nb_chambres;

    #[ORM\OneToMany(mappedBy: "id_hebergement", targetEntity: Chambre::class)]
    private Collection $chambres;

    public function __construct()
    {
        $this->chambres = new ArrayCollection();
        $this->note_moyenne = 0.0;
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

    public function getIdUser(): ?Utilisateur
    {
        return $this->id_user;
    }

    public function setIdUser(?Utilisateur $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
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

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getPays(): string
    {
        return $this->pays;
    }

    public function setPays(string $pays): self
    {
        $this->pays = $pays;
        return $this;
    }

    public function getNoteMoyenne(): float
    {
        return $this->note_moyenne;
    }

    public function setNoteMoyenne(float $note_moyenne): self
    {
        $this->note_moyenne = $note_moyenne;
        return $this;
    }

    public function getNote_Moyenne(): float
    {
        return $this->note_moyenne;
    }

    public function setNote_Moyenne(float $note_moyenne): self
    {
        $this->note_moyenne = $note_moyenne;
        return $this;
    }

    public function getServices(): string
    {
        return $this->services;
    }

    public function setServices(string $services): self
    {
        $this->services = $services;
        return $this;
    }

    public function getPolitiqueAnnulation(): string
    {
        return $this->politique_annulation;
    }

    public function setPolitiqueAnnulation(string $politique_annulation): self
    {
        $this->politique_annulation = $politique_annulation;
        return $this;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function setContact(string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getTypeHebergement(): string
    {
        return $this->type_hebergement;
    }

    public function setTypeHebergement(string $type_hebergement): self
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    public function getType_Hebergement(): string
    {
        return $this->type_hebergement;
    }

    public function setType_Hebergement(string $type_hebergement): self
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    public function getNbChambres(): int
    {
        return $this->nb_chambres;
    }

    public function setNbChambres(int $nb_chambres): self
    {
        $this->nb_chambres = $nb_chambres;
        return $this;
    }

    public function getChambres(): Collection
    {
        return $this->chambres;
    }

    public function addChambre(Chambre $chambre): self
    {
        if (!$this->chambres->contains($chambre)) {
            $this->chambres[] = $chambre;
            $chambre->setIdHebergement($this);
        }
        return $this;
    }

    public function removeChambre(Chambre $chambre): self
    {
        if ($this->chambres->removeElement($chambre)) {
            if ($chambre->getIdHebergement() === $this) {
                $chambre->setIdHebergement(null);
            }
        }
        return $this;
    }
}