<?php

declare(strict_types=1);

namespace App\Voice\Engine;

final readonly class PhraseCheckResult
{
    /**
     * @param array<string, mixed>|null $quality
     */
    public function __construct(
        public bool $accepted,
        public float $phraseMatch,
        public ?string $transcript = null,
        public ?array $quality = null,
        public ?string $reason = null,
    ) {
    }
}
