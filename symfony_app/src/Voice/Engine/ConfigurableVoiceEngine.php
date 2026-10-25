<?php

declare(strict_types=1);

namespace App\Voice\Engine;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Selects the engine through the VOICE_ENGINE environment variable.
 *
 *   VOICE_ENGINE=stub  -> deterministic placeholder, no Python needed
 *   VOICE_ENGINE=http  -> internal Python service (Whisper + models)
 */
#[AsAlias(VoiceEngineInterface::class)]
final class ConfigurableVoiceEngine implements VoiceEngineInterface
{
    public function __construct(
        private readonly StubVoiceEngine $stub,
        private readonly HttpVoiceEngine $http,
        #[Autowire('%env(VOICE_ENGINE)%')]
        private readonly string $engine,
    ) {
    }

    public function verify(string $audioPath, string $expectedPhrase): VerifyResult
    {
        return 'http' === $this->engine
            ? $this->http->verify($audioPath, $expectedPhrase)
            : $this->stub->verify($audioPath, $expectedPhrase);
    }
}
