<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Voyage;

#[ORM\Entity]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "username", type: "string", length: 255)]
    private string $username;

    #[ORM\Column(name: "password", type: "string", length: 255)]
    private string $password;

    #[ORM\Column(name: "email", type: "string", length: 255)]
    private string $email;

    #[ORM\Column(name: "numTel", type: "string", length: 20, nullable: true)]
    private ?string $numTel;

    #[ORM\Column(name: "address", type: "text", nullable: true)]
    private ?string $address;

    #[ORM\Column(name: "role", type: "string", length: 20)]
    private string $role;

    #[ORM\Column(name: "nom", type: "string", length: 40, nullable: true)]
    private ?string $nom;

    #[ORM\Column(name: "prenom", type: "string", length: 50, nullable: true)]
    private ?string $prenom;

    #[ORM\Column(name: "nomagence", type: "string", length: 40, nullable: true)]
    private ?string $nomagence;

    #[ORM\Column(name: "cin", type: "string", length: 40, nullable: true)]
    private ?string $cin;

    #[ORM\Column(name: "typeAgence", type: "string", length: 50, nullable: true)]
    private ?string $typeAgence;

    #[ORM\Column(name: "photo", type: "string", length: 255, nullable: true)]
    private ?string $photo;

    #[ORM\Column(name: "reset_token", type: "string", length: 255, nullable: true)]
    private ?string $reset_token;

    #[ORM\Column(name: "reset_token_expiry", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $reset_token_expiry;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Hebergement::class)]
    private Collection $hebergements;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Offre::class)]
    private Collection $offres;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Panier::class)]
    private Collection $paniers;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Post::class)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Reclamation::class)]
    private Collection $reclamations;

    #[ORM\OneToMany(mappedBy: "id_utilisateur", targetEntity: Review::class)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Commentaire::class)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Reaction::class)]
    private Collection $reactions;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Transport::class)]
    private Collection $transports;

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Voyage::class)]
    private Collection $voyages;

    public function __construct()
    {
        $this->hebergements = new ArrayCollection();
        $this->offres = new ArrayCollection();
        $this->paniers = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->transports = new ArrayCollection();
        $this->voyages = new ArrayCollection();
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

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->numTel;
    }

    public function setNumTel(?string $numTel): self
    {
        $this->numTel = $numTel;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNomagence(): ?string
    {
        return $this->nomagence;
    }

    public function setNomagence(?string $nomagence): self
    {
        $this->nomagence = $nomagence;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(?string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getTypeAgence(): ?string
    {
        return $this->typeAgence;
    }

    public function setTypeAgence(?string $typeAgence): self
    {
        $this->typeAgence = $typeAgence;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $reset_token): self
    {
        $this->reset_token = $reset_token;
        return $this;
    }

    public function getResetTokenExpiry(): ?\DateTimeInterface
    {
        return $this->reset_token_expiry;
    }

    public function setResetTokenExpiry(?\DateTimeInterface $reset_token_expiry): self
    {
        $this->reset_token_expiry = $reset_token_expiry;
        return $this;
    }

    public function getHebergements(): Collection
    {
        return $this->hebergements;
    }

    public function addHebergement(Hebergement $hebergement): self
    {
        if (!$this->hebergements->contains($hebergement)) {
            $this->hebergements[] = $hebergement;
            $hebergement->setId_User($this);
        }
        return $this;
    }

    public function removeHebergement(Hebergement $hebergement): self
    {
        if ($this->hebergements->removeElement($hebergement)) {
            if ($hebergement->getId_User() === $this) {
                $hebergement->setId_User(null);
            }
        }
        return $this;
    }

    public function getOffres(): Collection
    {
        return $this->offres;
    }

    public function addOffre(Offre $offre): self
    {
        if (!$this->offres->contains($offre)) {
            $this->offres[] = $offre;
            $offre->setId_User($this);
        }
        return $this;
    }

    public function removeOffre(Offre $offre): self
    {
        if ($this->offres->removeElement($offre)) {
            if ($offre->getId_User() === $this) {
                $offre->setId_User(null);
            }
        }
        return $this;
    }

    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): self
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers[] = $panier;
            $panier->setId_User($this);
        }
        return $this;
    }

    public function removePanier(Panier $panier): self
    {
        if ($this->paniers->removeElement($panier)) {
            if ($panier->getId_User() === $this) {
                $panier->setId_User(null);
            }
        }
        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setId_User($this);
        }
        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getId_User() === $this) {
                $post->setId_User(null);
            }
        }
        return $this;
    }

    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): self
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations[] = $reclamation;
            $reclamation->setId_User($this);
        }
        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): self
    {
        if ($this->reclamations->removeElement($reclamation)) {
            if ($reclamation->getId_User() === $this) {
                $reclamation->setId_User(null);
            }
        }
        return $this;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
            $review->setIdUtilisateur($this);
        }
        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getIdUtilisateur() === $this) {
                $review->setIdUtilisateur(null);
            }
        }
        return $this;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setId_User($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getId_User() === $this) {
                $commentaire->setId_User(null);
            }
        }
        return $this;
    }

    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(Reaction $reaction): self
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions[] = $reaction;
            $reaction->setId_User($this);
        }
        return $this;
    }

    public function removeReaction(Reaction $reaction): self
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getId_User() === $this) {
                $reaction->setId_User(null);
            }
        }
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
            $transport->setId_User($this);
        }
        return $this;
    }

    public function removeTransport(Transport $transport): self
    {
        if ($this->transports->removeElement($transport)) {
            if ($transport->getId_User() === $this) {
                $transport->setId_User(null);
            }
        }
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
            $voyage->setId_User($this);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): self
    {
        if ($this->voyages->removeElement($voyage)) {
            if ($voyage->getId_User() === $this) {
                $voyage->setId_User(null);
            }
        }
        return $this;
    }
}