<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;
use Doctrine\Common\Collections\Collection;
use App\Entity\Reservation;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Panier
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "paniers")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    #[ORM\Column(name: "prix_total", type: "float", nullable: true)]
    #[Assert\PositiveOrZero(message: "Le prix doit être positif ou égal à 0")]
    private ?float $prix_total = null;

    #[ORM\Column(name: "date_validation", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $date_validation = null;

    #[ORM\OneToMany(mappedBy: "id_panier", targetEntity: Reservation::class)]
    private Collection $reservations;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): void
    {
        $this->id = $value;
    }

    public function getIdUser(): Utilisateur
    {
        return $this->id_user;
    }

    public function setIdUser(Utilisateur $value): void
    {
        $this->id_user = $value;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prix_total;
    }

    public function setPrixTotal(?float $value): void
    {
        $this->prix_total = $value;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->date_validation;
    }

    public function setDateValidation(?\DateTimeInterface $value): void
    {
        $this->date_validation = $value;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setIdPanier($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getIdPanier() === $this) {
                $reservation->setIdPanier(null);
            }
        }

        return $this;
    }
}