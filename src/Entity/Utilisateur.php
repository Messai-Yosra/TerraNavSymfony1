<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Voyage;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà utilisé')]
class Utilisateur implements PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(name: "id", type: "integer")]
    private int $id;

    #[ORM\Column(name: "username", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom d'utilisateur ne peut pas être vide")]
    #[Assert\Length(
        min: 3, 
        max: 255, 
        minMessage: "Le nom d'utilisateur doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom d'utilisateur ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $username;

    #[ORM\Column(name: "password", type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide")]
    #[Assert\Length(
        min: 8,
        minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/",
        message: "Le mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule et un chiffre"
    )]
    private string $password;

    #[ORM\Column(name: "email", type: "string", length: 255)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private string $email;

    #[ORM\Column(name: "numTel", type: "string", length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}$/",
        message: "Le numéro de téléphone doit contenir 8 chiffres",
        match: true
    )]
    private ?string $numTel;

    #[ORM\Column(name: "address", type: "text", nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $address;

    #[ORM\Column(name: "role", type: "string", length: 20)]
    #[Assert\NotBlank(message: "Le rôle ne peut pas être vide")]
    #[Assert\Choice(
        choices: ["admin", "Client", "Agence"],
        message: "Le rôle doit être 'admin', 'Client' ou 'Agence'"
    )]
    private string $role;

    #[ORM\Column(name: "nom", type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide")]
    private ?string $nom = null;

    #[ORM\Column(name: "prenom", type: "string", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide")]
    private ?string $prenom = null;

    #[ORM\Column(name: "nomagence", type: "string", length: 40, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 40,
        minMessage: "Le nom de l'agence doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom de l'agence ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $nomagence;

    #[ORM\Column(name: "cin", type: "string", length: 40, nullable: true)]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}$/",
        message: "Le CIN doit contenir 8 chiffres",
        match: true
    )]
    private ?string $cin;

    #[ORM\Column(name: "typeAgence", type: "string", length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ["TRANSPORT", "HEBERGEMENT", "VOYAGE", "Mixte"],
        message: "Le type d'agence doit être 'TRANSPORT', 'HEBERGEMENT', 'VOYAGE' ou 'Mixte'",
        multiple: false
    )]
    private ?string $typeAgence;

    #[ORM\Column(name: "photo", type: "string", length: 255, nullable: true)]
    #[Assert\Image(
        maxSize: "1024k",
        mimeTypes: ["image/jpeg", "image/png", "image/gif"],
        mimeTypesMessage: "Veuillez télécharger une image valide (JPG, PNG ou GIF)"
    )]
    private ?string $photo;

    #[ORM\Column(name: "reset_token", type: "string", length: 255, nullable: true)]
    private ?string $reset_token;

    #[ORM\Column(name: "reset_token_expiry", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $reset_token_expiry;

    #[ORM\Column(name: "banned", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $banned = null;

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

    #[ORM\OneToMany(mappedBy: "idUser", targetEntity: Story::class)]
    private Collection $stories;

    #[ORM\Column(name: "has_facial_auth", type: "boolean", options: ["default" => false])]
    private bool $hasFacialAuth = false;

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
        $this->stories = new ArrayCollection();
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

    public function getRole(): ?string
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

    public function getBanned(): ?\DateTimeInterface
    {
        return $this->banned;
    }

    public function setBanned(?\DateTimeInterface $banned): self
    {
        $this->banned = $banned;
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->banned !== null;
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

    /**
     * Returns the roles granted to the user.
     *
     * @return string[] An array of roles
     */
    public function getRoles(): array
    {
        // Forcer un format cohérent
        $roles = ['ROLE_USER'];
        
        // Utiliser le rôle directement de la base de données
        $dbRole = $this->getRole();
        
        // Conversion selon votre convention
        if ($dbRole === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($dbRole === 'Agence') {
            $roles[] = 'ROLE_AGENCE';
            
            // Ajouter un rôle spécifique au type d'agence
            if ($this->getTypeAgence()) {
                switch ($this->getTypeAgence()) {
                    case 'TRANSPORT':
                        $roles[] = 'ROLE_AGENCE_TRANSPORT';
                        break;
                    case 'HEBERGEMENT':
                        $roles[] = 'ROLE_AGENCE_HEBERGEMENT';
                        break;
                    case 'VOYAGE':
                        $roles[] = 'ROLE_AGENCE_VOYAGE';
                        break;
                    case 'Mixte':
                        $roles[] = 'ROLE_AGENCE_MIXTE';
                        break;
                }
            }
        } elseif ($dbRole === 'Client') {
            $roles[] = 'ROLE_CLIENT';
        }
        
        return array_unique($roles);
    }
    
    /**
     * Set the roles granted to the user.
     *
     * @param array $roles The user roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        // This method is not implemented in this example
        return $this;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // For example, $this->plainPassword = null;
    }
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    
    public function getHasFacialAuth(): bool
    {
        return $this->hasFacialAuth;
    }
    
    public function setHasFacialAuth(bool $hasFacialAuth): self
    {
        $this->hasFacialAuth = $hasFacialAuth;
        return $this;
    }
    public function getStories(): Collection
{
    return $this->stories;
}

public function addStory(Story $story): self
{
    if (!$this->stories->contains($story)) {
        $this->stories[] = $story;
        $story->setIdUser($this);
    }
    return $this;
}

public function removeStory(Story $story): self
{
    if ($this->stories->removeElement($story)) {
        if ($story->getIdUser() === $this) {
            $story->setIdUser(null);
        }
    }
    return $this;
}
}