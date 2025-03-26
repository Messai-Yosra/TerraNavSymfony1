<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
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
    private string $nom;

    #[ORM\Column(name: "description", type: "text")]
    private string $description;

    #[ORM\Column(name: "adresse", type: "string", length: 255)]
    private string $adresse;

    #[ORM\Column(name: "ville", type: "string", length: 100)]
    private string $ville;

    #[ORM\Column(name: "pays", type: "string", length: 100)]
    private string $pays;

    #[ORM\Column(name: "note_moyenne", type: "float")]
    private float $note_moyenne;

    #[ORM\Column(name: "services", type: "text")]
    private string $services;

    #[ORM\Column(name: "politique_annulation", type: "text")]
    private string $politique_annulation;

    #[ORM\Column(name: "contact", type: "string", length: 255)]
    private string $contact;

    #[ORM\Column(name: "type_hebergement", type: "string", length: 50)]
    private string $type_hebergement;

    #[ORM\Column(name: "nb_chambres", type: "integer")]
    private int $nb_chambres;

    #[ORM\OneToMany(mappedBy: "id_hebergement", targetEntity: Chambre::class)]
    private Collection $chambres;

    public function __construct()
    {
        $this->chambres = new ArrayCollection();
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

    public function getid_user(): ?Utilisateur
    {
        return $this->id_user;
    }

    public function setid_user(?Utilisateur $id_user): self
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

    public function getnote_moyenne(): float
    {
        return $this->note_moyenne;
    }

    public function setnote_moyenne(float $note_moyenne): self
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

    public function getpolitique_annulation(): string
    {
        return $this->politique_annulation;
    }

    public function setpolitique_annulation(string $politique_annulation): self
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

    public function gettype_hebergement(): string
    {
        return $this->type_hebergement;
    }

    public function settype_hebergement(string $type_hebergement): self
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    public function getnb_chambres(): int
    {
        return $this->nb_chambres;
    }

    public function setnb_chambres(int $nb_chambres): self
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
            $chambre->setid_hebergement($this);
        }
        return $this;
    }

    public function removeChambre(Chambre $chambre): self
    {
        if ($this->chambres->removeElement($chambre)) {
            if ($chambre->getid_hebergement() === $this) {
                $chambre->setid_hebergement(null);
            }
        }
        return $this;
    }
}