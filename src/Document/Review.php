<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use App\Entity\Trip;
use App\Entity\User;

#[MongoDB\Document]
class Review
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $userId;

    #[MongoDB\Field(type: 'string', nullable: true)]
    private ?string $userName = null;

    #[MongoDB\Field(type: 'string')]
    private string $userEmail;

    #[MongoDB\Field(type: 'int')]
    private int $tripId;

    #[MongoDB\Field(type: 'int')]
    private int $reviewerId;

    #[MongoDB\Field(type: 'int')]
    private int $driverId;

    #[MongoDB\Field(type: 'int', nullable: true)]
    private ?int $note = null;

    #[MongoDB\Field(type: 'string', nullable: true)]
    private ?string $commentaire = null;

    #[MongoDB\Field(type: 'bool')]
    private bool $valide = false;

    #[MongoDB\Field(type: 'bool')]
    private bool $isIncident = false;

    #[MongoDB\Field(type: 'string', nullable: true)]
    private ?string $incidentDescription = null;

    #[MongoDB\Field(type: 'string')]
    private string $etat = 'en_attente';

    #[MongoDB\Field(type: 'date')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Initialise une review à partir d'un Trip et d'un User
     */
    public static function fromTrip(Trip $trip, User $user): self
    {
        $review = new self();
        $review->setUserId((string) $user->getId());
        $review->setUserEmail($user->getEmail());
        $review->setUserName($user->getUsername());
        $review->setTripId($trip->getId());
        $review->setReviewerId((int) $user->getId());
        $review->setDriverId($trip->getDriver()->getId());

        return $review;
    }

    // --- Getters / Setters ---
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName ?? 'Inconnu';
    }

    public function setUserName(?string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getTripId(): int
    {
        return $this->tripId;
    }

    public function setTripId(int $tripId): self
    {
        $this->tripId = $tripId;
        return $this;
    }

    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    public function setReviewerId(int $reviewerId): self
    {
        $this->reviewerId = $reviewerId;
        return $this;
    }

    public function getDriverId(): int
    {
        return $this->driverId;
    }

    public function setDriverId(int $driverId): self
    {
        $this->driverId = $driverId;
        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function isValide(): bool
    {
        return $this->valide;
    }

    public function setValide(bool $valide): self
    {
        $this->valide = $valide;
        return $this;
    }

    public function isIncident(): bool
    {
        return $this->isIncident;
    }

    public function setIsIncident(bool $isIncident): self
    {
        $this->isIncident = $isIncident;
        return $this;
    }

    public function getIncidentDescription(): ?string
    {
        return $this->incidentDescription;
    }

    public function setIncidentDescription(?string $incidentDescription): self
    {
        $this->incidentDescription = $incidentDescription;
        return $this;
    }

    public function getEtat(): string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
