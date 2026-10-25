<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Voice\Challenge\ChallengeStore;
use App\Voice\Phrase\PhraseGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/challenge', name: 'api_challenge', methods: ['POST'])]
final class ChallengeController extends AbstractController
{
    public function __invoke(
        Request $request,
        PhraseGenerator $generator,
        ChallengeStore $store,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('aurix', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'invalid_csrf'], 403);
        }

        $challenge = $store->create($generator->generate());

        return $this->json([
            'id' => $challenge->id,
            'phrase' => $challenge->phrase,
            'expiresAt' => $challenge->expiresAt->format(\DateTimeInterface::ATOM),
        ]);
    }
}
