<?php

declare(strict_types=1);

namespace App\Voice\Challenge;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores the active challenge in the Symfony session.
 *
 * No user accounts are required yet: the session identifies the browser.
 * A challenge is removed as soon as it is consumed, so it cannot be replayed.
 */
final class ChallengeStore
{
    private const SESSION_KEY = 'aurix_challenge';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function create(string $phrase, int $ttlSeconds = 120): Challenge
    {
        $challenge = new Challenge(
            id: bin2hex(random_bytes(16)),
            phrase: $phrase,
            expiresAt: new \DateTimeImmutable(sprintf('+%d seconds', $ttlSeconds)),
        );

        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $challenge);

        return $challenge;
    }

    public function consume(string $id): ?Challenge
    {
        $session = $this->requestStack->getSession();
        $challenge = $session->get(self::SESSION_KEY);
        $session->remove(self::SESSION_KEY);

        if (!$challenge instanceof Challenge || !hash_equals($challenge->id, $id)) {
            return null;
        }

        return $challenge->isExpired() ? null : $challenge;
    }
}
