<?php

declare(strict_types=1);

namespace App\Voice\Engine;

/**
 * Deterministic placeholder used when VOICE_ENGINE=stub.
 */
final class StubVoiceEngine implements VoiceEngineInterface
{
    public function verify(
        string $audioPath,
        string $expectedPhrase,
        ?string $referenceAudioPath = null,
    ): VerifyResult {
        $size = max(1, (int) filesize($audioPath));

        $similarity = 0.92;
        $padScore = 0.99;
        $phraseMatch = 0.95;

        if (null === $referenceAudioPath) {
            return new VerifyResult(
                accepted: false,
                similarity: 0.0,
                padScore: $padScore,
                phraseMatch: $phraseMatch,
                reason: 'speaker reference required',
                stub: true,
            );
        }

        return new VerifyResult(
            accepted: $similarity >= 0.80 && $phraseMatch >= 0.80,
            similarity: $similarity,
            padScore: $padScore,
            phraseMatch: $phraseMatch,
            reason: null,
            stub: true,
        );
    }

    public function checkPhrase(string $audioPath, string $expectedPhrase): PhraseCheckResult
    {
        return new PhraseCheckResult(
            accepted: true,
            phraseMatch: 0.95,
            transcript: $expectedPhrase,
            quality: ['speech_duration' => 2.0, 'estimated_snr_db' => 35.0],
        );
    }
}
