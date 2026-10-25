<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Voice\Challenge\ChallengeStore;
use App\Voice\Engine\VoiceEngineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/verify', name: 'api_verify', methods: ['POST'])]
final class VerifyController extends AbstractController
{
    private const MAX_AUDIO_BYTES = 32 * 1024 * 1024;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        Request $request,
        ChallengeStore $store,
        VoiceEngineInterface $engine,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('aurix', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'invalid_csrf'], 403);
        }

        $challenge = $store->consume((string) $request->request->get('challengeId', ''));

        if (null === $challenge) {
            return $this->json([
                'error' => 'challenge_expired',
                'message' => 'Fraza wygasła lub została już użyta.',
            ], 410);
        }

        $audio = $request->files->get('audio');

        if (!$audio instanceof UploadedFile || !$audio->isValid()) {
            return $this->json([
                'error' => 'missing_audio',
                'message' => 'Brak pliku audio w polu "audio".',
            ], 400);
        }

        if ($audio->getSize() > self::MAX_AUDIO_BYTES) {
            return $this->json([
                'error' => 'audio_too_large',
                'message' => 'Maksymalny rozmiar pliku to 32 MB.',
            ], 413);
        }

        try {
            $result = $engine->verify($audio->getPathname(), $challenge->phrase);
        } catch (\Throwable $exception) {
            $this->logger->error('Voice engine failed.', ['exception' => $exception]);

            return $this->json([
                'error' => 'engine_unavailable',
                'message' => 'Silnik weryfikacji jest chwilowo niedostępny.',
            ], 502);
        }

        return $this->json([
            'accepted' => $result->accepted,
            'similarity' => $result->similarity,
            'padScore' => $result->padScore,
            'phraseMatch' => $result->phraseMatch,
            'reason' => $result->reason,
            'stub' => $result->stub,
        ]);
    }
}
