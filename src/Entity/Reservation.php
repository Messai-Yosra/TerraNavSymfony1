<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Chambre;
use App\Entity\Panier;
use App\Entity\Voyage;
use App\Entity\Transport;

#[ORM\Entity]
class Reservation
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: "id_panier", referencedColumnName: "id", onDelete: "CASCADE")]
    private Panier $id_panier;

    #[ORM\ManyToOne(targetEntity: Voyage::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: "id_voyage", referencedColumnName: "id", onDelete: "CASCADE", nullable: true)]
    private ?Voyage $id_voyage = null;

    #[ORM\ManyToOne(targetEntity: Transport::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: "id_Transport", referencedColumnName: "id", onDelete: "CASCADE", nullable: true)]
    private ?Transport $id_Transport = null;

    #[ORM\Column(name: "type_service", type: "text", nullable: true)]
    private ?string $type_service = null;

    #[ORM\Column(name: "prix", type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(name: "date_reservation", type: "datetime", nullable: true, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $date_reservation = null;

    #[ORM\Column(name: "Etat", type: "string", length: 10)]
    private string $Etat;

    #[ORM\Column(name: "nb_places", type: "integer", nullable: true)]
    private ?int $nb_places = null;

    #[ORM\ManyToOne(targetEntity: Chambre::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: "id_Chambre", referencedColumnName: "id", onDelete: "CASCADE", nullable: true)]
    private ?Chambre $id_Chambre = null;

    #[ORM\Column(name: "nbJoursHebergement", type: "integer", nullable: true)]
    private ?int $nbJoursHebergement = null;

    #[ORM\Column(name: "dateAffectation", type: "datetime", nullable: true, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $dateAffectation = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): void
    {
        $this->id = $value;
    }

    public function getid_panier(): Panier
    {
        return $this->id_panier;
    }

    public function setid_panier(Panier $value): void
    {
        $this->id_panier = $value;
    }

    public function getid_voyage(): ?Voyage
    {
        return $this->id_voyage;
    }

    public function setid_voyage(?Voyage $value): void
    {
        $this->id_voyage = $value;
    }

    public function getid_Transport(): ?Transport
    {
        return $this->id_Transport;
    }

    public function setid_Transport(?Transport $value): void
    {
        $this->id_Transport = $value;
    }

    public function gettype_service(): ?string
    {
        return $this->type_service;
    }

    public function settype_service(?string $value): void
    {
        $this->type_service = $value;
    }

    public function getprix(): ?string
    {
        return $this->prix;
    }

    public function setprix(?string $value): void
    {
        $this->prix = $value;
    }

    public function getdate_reservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setdate_reservation(?\DateTimeInterface $value): void
    {
        $this->date_reservation = $value;
    }

    public function getEtat(): string
    {
        return $this->Etat;
    }

    public function setEtat(string $value): void
    {
        $this->Etat = $value;
    }

    public function getnb_places(): ?int
    {
        return $this->nb_places;
    }

    public function setnb_places(?int $value): void
    {
        $this->nb_places = $value;
    }

    public function getid_Chambre(): ?Chambre
    {
        return $this->id_Chambre;
    }

    public function setid_Chambre(?Chambre $value): void
    {
        $this->id_Chambre = $value;
    }

    public function getnbJoursHebergement(): ?int
    {
        return $this->nbJoursHebergement;
    }

    public function setnbJoursHebergement(?int $value): void
    {
        $this->nbJoursHebergement = $value;
    }

    public function getdateAffectation(): ?\DateTimeInterface
    {
        return $this->dateAffectation;
    }

    public function setdateAffectation(?\DateTimeInterface $value): void
    {
        $this->dateAffectation = $value;
    }
}