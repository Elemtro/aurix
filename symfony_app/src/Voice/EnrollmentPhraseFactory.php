<?php

declare(strict_types=1);

namespace App\Voice;

use App\Voice\Phrase\PhraseGenerator;

final class EnrollmentPhraseFactory
{
    public function __construct(
        private readonly PhraseGenerator $generator,
    ) {
    }

    /**
     * @param list<string> $avoid
     *
     * @return list<string>
     */
    public function generateUnique(int $count, array $avoid = []): array
    {
        $result = [];

        while (count($result) < $count) {
            $phrase = $this->generator->generate();

            if (in_array($phrase, $avoid, true) || in_array($phrase, $result, true)) {
                continue;
            }

            $result[] = $phrase;
        }

        return $result;
    }
}
