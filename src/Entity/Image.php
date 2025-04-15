<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Chambre;

#[ORM\Entity]
class Image
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "integer")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Chambre::class, inversedBy: "images")]
    #[ORM\JoinColumn(name: 'id_chambre', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Chambre $id_chambre;

    #[ORM\Column(name: "url_image", type: "string", length: 255)]
    private string $url_image;
    #[ORM\Column(name: "image_name", type: "string", length: 255, nullable: true)]
    private ?string $imageName = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): void
    {
        $this->id = $value;
    }

    public function getIdChambre(): Chambre
    {
        return $this->id_chambre;
    }

    public function setIdChambre(Chambre $value): void
    {
        $this->id_chambre = $value;
    }

    public function getUrlImage(): string
    {
        return $this->url_image;
    }

    public function setUrlImage(string $value): void
    {
        $this->url_image = $value;
    }
}