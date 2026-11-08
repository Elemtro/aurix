<?php

declare(strict_types=1);

namespace App\Voice\Engine;

final class VoiceEngineException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
    ) {
        parent::__construct($message);
    }
}
