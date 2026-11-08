<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VoiceSampleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoiceSampleRepository::class)]
class VoiceSample
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'voiceSamples')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private int $phraseIndex = 0;

    #[ORM\Column(length: 255)]
    private string $phrase = '';

    /**
     * Encrypted reference audio (base64 nonce + secretbox ciphertext).
     */
    #[ORM\Column(type: 'text')]
    private string $audioCiphertext = '';

    #[ORM\Column(length: 100)]
    private string $mimeType = 'audio/webm';

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

    public function getAudioCiphertext(): string
    {
        return $this->audioCiphertext;
    }

    public function setAudioCiphertext(string $audioCiphertext): self
    {
        $this->audioCiphertext = $audioCiphertext;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
