<?php

declare(strict_types=1);

namespace App\Voice;

/**
 * Tolerant phrase comparison.
 *
 * Whisper sometimes merges adjacent words ("torba lato" -> "Torbalato").
 * The ML scores word-by-word, so this matcher adds a space-insensitive
 * comparison as a fallback and returns the better of the two scores.
 */
final class PhraseMatcher
{
    public function score(string $expected, string $transcript): float
    {
        $expected = $this->normalize($expected);
        $transcript = $this->normalize($transcript);

        if ('' === $expected || '' === $transcript) {
            return 0.0;
        }

        if ($expected === $transcript) {
            return 1.0;
        }

        return max(
            $this->wordScore($expected, $transcript),
            $this->compactScore($expected, $transcript),
        );
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    private function wordScore(string $expected, string $transcript): float
    {
        $expectedWords = explode(' ', $expected);
        $heardWords = explode(' ', $transcript);
        $matched = 0;

        foreach ($expectedWords as $word) {
            foreach ($heardWords as $heard) {
                similar_text($word, $heard, $percent);

                if ($percent >= 80.0) {
                    ++$matched;
                    break;
                }
            }
        }

        return $matched / max(1, count($expectedWords));
    }

    private function compactScore(string $expected, string $transcript): float
    {
        $expected = str_replace(' ', '', $expected);
        $transcript = str_replace(' ', '', $transcript);

        similar_text($expected, $transcript, $percent);

        return $percent / 100.0;
    }
}
