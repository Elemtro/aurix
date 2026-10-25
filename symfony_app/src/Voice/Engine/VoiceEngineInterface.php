<?php

declare(strict_types=1);

namespace App\Voice\Engine;

/**
 * Boundary between the web application and the voice verification engine.
 *
 * Phase 1: StubVoiceEngine.
 * Phase 2: an HTTP client talking to the internal Python service.
 */
interface VoiceEngineInterface
{
    public function verify(string $audioPath, string $expectedPhrase): VerifyResult;
}
