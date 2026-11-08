<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserPhraseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPhraseRepository::class)]
#[ORM\Table(name: 'user_phrase')]
#[ORM\UniqueConstraint(name: 'uniq_user_phrase_index', columns: ['user_id', 'phrase_index'])]
class UserPhrase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userPhrases')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private int $phraseIndex = 0;

    #[ORM\Column(length: 255)]
    private string $phrase = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPhraseIndex(): int
    {
        return $this->phraseIndex;
    }

    public function setPhraseIndex(int $phraseIndex): self
    {
        $this->phraseIndex = $phraseIndex;

        return $this;
    }

    public function getPhrase(): string
    {
        return $this->phrase;
    }

    public function setPhrase(string $phrase): self
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
