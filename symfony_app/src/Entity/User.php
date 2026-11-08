<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $username = '';

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enrolledAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, VoiceSample> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VoiceSample::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $voiceSamples;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->voiceSamples = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
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

    public function getEnrolledAt(): ?\DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(?\DateTimeImmutable $enrolledAt): self
    {
        $this->enrolledAt = $enrolledAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, VoiceSample>
     */
    public function getVoiceSamples(): Collection
    {
        return $this->voiceSamples;
    }

    public function addVoiceSample(VoiceSample $voiceSample): self
    {
        if (!$this->voiceSamples->contains($voiceSample)) {
            $this->voiceSamples->add($voiceSample);
            $voiceSample->setUser($this);
        }

        return $this;
    }

    public function removeVoiceSample(VoiceSample $voiceSample): self
    {
        if ($this->voiceSamples->removeElement($voiceSample) && $voiceSample->getUser() === $this) {
            $voiceSample->setUser(null);
        }

        return $this;
    }

    public function isEnrolled(): bool
    {
        return null !== $this->enrolledAt && $this->voiceSamples->count() >= 3;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
    }
}
