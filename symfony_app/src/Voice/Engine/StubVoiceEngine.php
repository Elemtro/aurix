<?php

declare(strict_types=1);

namespace App\Voice\Engine;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Deterministic placeholder engine used until the Python service is ready.
 *
 * Scores are derived from the audio size so repeated runs with the same
 * recording are stable. This keeps the whole flow testable end to end.
 */
#[AsAlias(VoiceEngineInterface::class)]
final class StubVoiceEngine implements VoiceEngineInterface
{
    private const SIMILARITY_THRESHOLD = 0.80;
    private const PAD_THRESHOLD = 0.90;
    private const PHRASE_THRESHOLD = 0.80;

    public function verify(string $audioPath, string $expectedPhrase): VerifyResult
    {
        $size = max(1, (int) filesize($audioPath));

        $similarity = 0.70 + (($size % 25) / 100);
        $padScore = 0.99;
        $phraseMatch = 0.95;

        $accepted = $similarity >= self::SIMILARITY_THRESHOLD
            && $padScore >= self::PAD_THRESHOLD
            && $phraseMatch >= self::PHRASE_THRESHOLD;

        return new VerifyResult(
            accepted: $accepted,
            similarity: $similarity,
            padScore: $padScore,
            phraseMatch: $phraseMatch,
            reason: $accepted ? null : 'similarity below threshold',
            stub: true,
        );
    }
}
