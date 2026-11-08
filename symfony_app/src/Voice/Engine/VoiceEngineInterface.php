<?php

declare(strict_types=1);

namespace App\Voice\Engine;

interface VoiceEngineInterface
{
    public function verify(
        string $audioPath,
        string $expectedPhrase,
        ?string $referenceAudioPath = null,
    ): VerifyResult;

    public function checkPhrase(string $audioPath, string $expectedPhrase): PhraseCheckResult;
}
