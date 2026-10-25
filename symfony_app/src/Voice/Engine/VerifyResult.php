<?php

declare(strict_types=1);

namespace App\Voice\Engine;

/**
 * Raw scores returned by the voice engine.
 *
 * The final accept/deny decision belongs to the application layer,
 * so thresholds can change without redeploying the model service.
 */
final readonly class VerifyResult
{
    public function __construct(
        public bool $accepted,
        public float $similarity,
        public float $padScore,
        public float $phraseMatch,
        public ?string $reason = null,
        public bool $stub = false,
    ) {
    }
}
