<?php

declare(strict_types=1);

namespace App\Voice\Engine;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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

    public function verify(
        string $audioPath,
        string $expectedPhrase,
        ?string $referenceAudioPath = null,
    ): VerifyResult {
        return 'http' === $this->engine
            ? $this->http->verify($audioPath, $expectedPhrase, $referenceAudioPath)
            : $this->stub->verify($audioPath, $expectedPhrase, $referenceAudioPath);
    }

    public function checkPhrase(string $audioPath, string $expectedPhrase): PhraseCheckResult
    {
        return 'http' === $this->engine
            ? $this->http->checkPhrase($audioPath, $expectedPhrase)
            : $this->stub->checkPhrase($audioPath, $expectedPhrase);
    }
}
