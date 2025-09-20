<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(options: ["default" => 20])]
    private int $credits = 20;

    #[ORM\Column(options: ["default" => true])]
    private bool $actif = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isSuspended = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Vehicule::class, cascade: ['persist', 'remove'])]
    private Collection $vehicules;

    #[ORM\OneToMany(mappedBy: "driver", targetEntity: Trip::class)]
    private Collection $trips;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Participation::class, orphanRemoval: true)]
    private Collection $participations;

    public const ROLES = [
        'ROLE_USER',
        'ROLE_CHAUFFEUR',
        'ROLE_PASSAGER',
        'ROLE_EMPLOYE',
        'ROLE_ADMIN'
    ];

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
        $this->trips = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->date_creation = new \DateTime();
    }

    // --- Identité & sécurité ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        foreach ($roles as $role) {
            if (!in_array($role, self::ROLES, true)) {
                throw new \InvalidArgumentException("Role invalide : $role");
            }
        }

        if (in_array('ROLE_ADMIN', $roles, true) && in_array('ROLE_EMPLOYE', $roles, true)) {
            throw new \InvalidArgumentException("ROLE_ADMIN et ROLE_EMPLOYE sont exclusifs.");
        }

        $this->roles = $roles;
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

    public function eraseCredentials(): void {}

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    // --- Crédits & état ---
    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = max(0, $credits); // évite valeurs négatives
        return $this;
    }

    public function addCredits(int $amount): self
    {
        $this->credits += max(0, $amount);
        return $this;
    }

    public function removeCredits(int $amount): self
    {
        $this->credits = max(0, $this->credits - $amount);
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function isEnabled(): bool
    {
        return !$this->isSuspended;
    }

    public function getIsSuspended(): bool
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(bool $isSuspended): self
    {
        $this->isSuspended = $isSuspended;
        return $this;
    }

    // --- Relations Vehicules ---
    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): self
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules[] = $vehicule;
            $vehicule->setUser($this);
        }
        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): self
    {
        if ($this->vehicules->removeElement($vehicule)) {
            if ($vehicule->getUser() === $this) {
                $vehicule->setUser(null);
            }
        }
        return $this;
    }

    // --- Relations Trips (trajets créés comme chauffeur) ---
    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): self
    {
        if (!$this->trips->contains($trip)) {
            $this->trips[] = $trip;
            $trip->setDriver($this);
        }
        return $this;
    }

    public function removeTrip(Trip $trip): self
    {
        if ($this->trips->removeElement($trip)) {
            if ($trip->getDriver() === $this) {
                $trip->setDriver(null);
            }
        }
        return $this;
    }

    // --- Relations Participations (réservations comme passager) ---
    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setUser($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }
        return $this;
    }
}
