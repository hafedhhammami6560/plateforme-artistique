<?php

namespace App\Entity;

use App\Repository\OrganisationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Organisation - Représente une structure artistique ou culturelle
 * 
 * Cette classe gère les organisations (galeries, associations, structures)
 * qui peuvent être associées à une communauté (Communite).
 * 
 * Exemples d'organisations:
 * - Galeries d'art
 * - Associations culturelles
 * - Studios de création
 * - Collectifs d'artistes
 */
#[ORM\Entity(repositoryClass: OrganisationRepository::class)]
#[ORM\Table(name: 'organisation')]
class Organisation
{
    /**
     * Identifiant unique auto-incrémenté
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Nom de l'organisation
     * Exemple: "Galerie d'Art Moderne", "Association des Peintres"
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    /**
     * Adresse physique de l'organisation (optionnelle)
     * Exemple: "123 Rue de la Paix, 75001 Paris"
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    /**
     * Email de contact de l'organisation (optionnel)
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * Date de création de l'enregistrement
     * Définie automatiquement lors de la construction
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Nom de l'utilisateur ayant créé l'organisation
     * Valeur par défaut: 'admin' (système statique pour l'instant)
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 100)]
    private ?string $createdBy = null;

    /**
     * Communauté à laquelle appartient l'organisation (optionnelle)
     * Relation ManyToOne: Plusieurs organisations peuvent appartenir à une communauté
     * @var Communite|null
     */
    #[ORM\ManyToOne(targetEntity: Communite::class, inversedBy: 'organisations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Communite $communite = null;

    /**
     * Date/heure de l'événement associé (optionnel)
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $eventDate = null;

    /**
     * Type d'événement (ex: exposition, atelier, rencontre)
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $eventType = null;

    /**
     * Adresse complète de l'emplacement (récupérée via Google Maps)
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $locationAddress = null;

    /**
     * Latitude de l'emplacement
     * @var float|null
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $locationLat = null;

    /**
     * Longitude de l'emplacement
     * @var float|null
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $locationLng = null;

    /**
     * Constructeur - Initialise les valeurs par défaut
     */
    public function __construct()
    {
        // Définir la date de création à maintenant
        $this->createdAt = new \DateTime();
        // Créateur par défaut (système statique)
        $this->createdBy = 'admin';
        // Statut par défaut: approved pour conserver le comportement existant
        $this->status = self::STATUS_APPROVED;
    }

    // ========== GETTERS & SETTERS ==========

    /**
     * Récupère l'ID de l'organisation
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère le nom de l'organisation
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom de l'organisation
     * @param string $name Le nouveau nom
     * @return static Pour le chaînage de méthodes
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Récupère l'adresse de l'organisation
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Définit l'adresse physique
     * 
     * @param string|null $address Nouvelle adresse
     * @return static Instance courante (fluent interface)
     */
    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'email de contact
     * 
     * @param string|null $email Nouvel email
     * @return static Instance courante (fluent interface)
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création
     * 
     * @param \DateTimeInterface $createdAt Date de création
     * @return static Instance courante (fluent interface)
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Définit l'auteur (créateur) de l'organisation
     * 
     * @param string $createdBy Identifiant de l'utilisateur créateur
     * @return static Instance courante (fluent interface)
     */
    public function setCreatedBy(string $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCommunite(): ?Communite
    {
        return $this->communite;
    }

    /**
     * Définit la communauté parent (relation ManyToOne)
     * 
     * Permet d'associer cette organisation à une communauté
     * Une organisation peut appartenir à 0 ou 1 communauté
     * 
     * @param Communite|null $communite Communauté parent ou null
     * @return static Instance courante (fluent interface)
     */
    public function setCommunite(?Communite $communite): static
    {
        $this->communite = $communite;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getLocationAddress(): ?string
    {
        return $this->locationAddress;
    }

    public function setLocationAddress(?string $locationAddress): static
    {
        $this->locationAddress = $locationAddress;
        return $this;
    }

    public function getLocationLat(): ?float
    {
        return $this->locationLat;
    }

    public function setLocationLat(?float $locationLat): static
    {
        $this->locationLat = $locationLat;
        return $this;
    }

    public function getLocationLng(): ?float
    {
        return $this->locationLng;
    }

    public function setLocationLng(?float $locationLng): static
    {
        $this->locationLng = $locationLng;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    /**
     * Statuts possibles pour une organisation (workflow d'approbation)
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}
