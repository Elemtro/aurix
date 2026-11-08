<?php

declare(strict_types=1);

namespace App\Voice;

/**
 * Turns the ML service's English error details into user-facing Polish.
 */
final class VoiceErrorTranslator
{
    public function translate(string $message): string
    {
        return match (true) {
            str_contains($message, 'No speech detected') => 'Nie wykryto mowy. Nagraj wyraźną frazę i spróbuj ponownie.',
            str_contains($message, 'At least') => 'Nagranie jest zbyt krótkie. Powiedz całą frazę.',
            str_contains($message, 'exceeds') => 'Nagranie jest zbyt długie.',
            str_contains($message, 'decoding failed') => 'Nie udało się odczytać nagrania.',
            str_contains($message, 'Reference audio') => 'Nagranie referencyjne jest zbyt krótkie.',
            default => $message,
        };
    }
}
