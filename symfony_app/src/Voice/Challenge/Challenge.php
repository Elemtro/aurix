<?php

declare(strict_types=1);

namespace App\Voice\Challenge;

/**
 * A single-use verification challenge tied to the current session.
 */
final readonly class Challenge
{
    public function __construct(
        public string $id,
        public string $phrase,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }
}
