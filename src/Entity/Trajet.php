<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Transport;

#[ORM\Entity]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "pointDepart", type: "string", length: 50, nullable: true)]
    private ?string $pointDepart;

    #[ORM\Column(name: "destination", type: "string", length: 50, nullable: true)]
    private ?string $destination;

    #[ORM\Column(name: "dateDepart", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $dateDepart;

    #[ORM\Column(name: "duree", type: "integer", nullable: true)]
    private ?int $duree;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description;

    #[ORM\Column(name: "disponibilite", type: "boolean", options: ["default" => 1])]
    private ?int $disponibilite;

    #[ORM\OneToMany(mappedBy: "id_trajet", targetEntity: Transport::class)]
    private Collection $transports;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
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