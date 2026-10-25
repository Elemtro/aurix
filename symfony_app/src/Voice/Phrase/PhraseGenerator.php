<?php

declare(strict_types=1);

namespace App\Voice\Phrase;

/**
 * Builds a random challenge phrase: three words plus two number words.
 *
 * Uses random_int() (CSPRNG) so the phrase cannot be predicted from
 * previous challenges. Duplicates within one phrase are avoided.
 */
final class PhraseGenerator
{
    public function generate(): string
    {
        $words = $this->pick(WordList::WORDS, 3);
        $numbers = $this->pick(WordList::NUMBERS, 2);

        return implode(' ', [...$words, ...$numbers]);
    }

    /**
     * @param list<string> $pool
     *
     * @return list<string>
     */
    private function pick(array $pool, int $count): array
    {
        $picked = [];
        $max = count($pool) - 1;

        while (count($picked) < $count) {
            $value = $pool[random_int(0, $max)];

            if (!in_array($value, $picked, true)) {
                $picked[] = $value;
            }
        }

        return $picked;
    }
}
