<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Transport;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "pointDepart", type: "string", length: 50, nullable: true)]
    #[Assert\NotBlank(message: "Le point de départ est requis")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le point de départ doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le point de départ ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s\-]+$/",
        message: "Le point de départ ne peut contenir que des lettres, espaces ou tirets"
    )]
    private ?string $pointDepart;

    #[ORM\Column(name: "destination", type: "string", length: 50, nullable: true)]
    #[Assert\NotBlank(message: "La destination est requise")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "La destination doit contenir au moins {{ limit }} caractères",
        maxMessage: "La destination ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s\-]+$/",
        message: "La destination ne peut contenir que des lettres, espaces ou tirets"
    )]
    private ?string $destination;
    #[ORM\Column(name: "dateDepart", type: "datetime", nullable: true)]
    #[Assert\NotBlank(message: "La date de départ est requise")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "La date de départ ne peut pas être dans le passé"
    )]
    private ?\DateTimeInterface $dateDepart;

    #[ORM\Column(name: "duree", type: "integer", nullable: true)]
    #[Assert\NotBlank(message: "La durée est requise")]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[Assert\Range(
        min: 1,
        max: 1440,
        notInRangeMessage: "La durée doit être entre {{ min }} et {{ max }} minutes"
    )]
    private ?int $duree;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description;

    #[ORM\Column(name: "disponibilite", type: "boolean", options: ["default" => 1])]
    private ?bool $disponibilite;

    #[ORM\OneToMany(mappedBy: "id_trajet", targetEntity: Transport::class)]
    private Collection $transports;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
        $this->disponibilite = true;
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

    public function getPointDepart(): ?string
    {
        return $this->pointDepart;
    }

    public function setPointDepart(?string $pointDepart): self
    {
        $this->pointDepart = $pointDepart;
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

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(?\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;
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

    public function getDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?bool $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getTransports(): Collection
    {
        return $this->transports;
    }

    public function addTransport(Transport $transport): self
    {
        if (!$this->transports->contains($transport)) {
            $this->transports[] = $transport;
            $transport->setId_trajet($this);
        }
        return $this;
    }

    public function removeTransport(Transport $transport): self
    {
        if ($this->transports->removeElement($transport)) {
            if ($transport->getId_trajet() === $this) {
                $transport->setId_trajet(null);
            }
        }
        return $this;
    }
}