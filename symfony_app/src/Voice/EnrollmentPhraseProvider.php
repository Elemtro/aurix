<?php

declare(strict_types=1);

namespace App\Voice;

use App\Entity\User;
use App\Entity\UserPhrase;
use App\Repository\UserPhraseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Guarantees that every user has exactly three enrollment phrases.
 *
 * Existing samples are kept consistent: if a user already recorded a phrase
 * before this feature existed, its text is reused instead of generating a
 * different one.
 */
final class EnrollmentPhraseProvider
{
    public function __construct(
        private readonly UserPhraseRepository $phrases,
        private readonly EnrollmentPhraseFactory $factory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function ensureForUser(User $user): array
    {
        $existing = [];

        foreach ($this->phrases->findForUser($user) as $row) {
            $existing[$row->getPhraseIndex()] = $row->getPhrase();
        }

        foreach ($user->getVoiceSamples() as $sample) {
            $index = $sample->getPhraseIndex();

            if (!isset($existing[$index])) {
                $row = (new UserPhrase())
                    ->setUser($user)
                    ->setPhraseIndex($index)
                    ->setPhrase($sample->getPhrase());

                $this->entityManager->persist($row);
                $existing[$index] = $sample->getPhrase();
            }
        }

        $missing = array_values(array_diff([1, 2, 3], array_keys($existing)));

        if ([] !== $missing) {
            $generated = $this->factory->generateUnique(count($missing), array_values($existing));

            foreach ($missing as $position => $index) {
                $row = (new UserPhrase())
                    ->setUser($user)
                    ->setPhraseIndex($index)
                    ->setPhrase($generated[$position]);

                $this->entityManager->persist($row);
                $existing[$index] = $generated[$position];
            }

            $this->entityManager->flush();
        }

        ksort($existing);

        return $existing;
    }
}
