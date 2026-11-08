<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\VoiceSample;
use App\Repository\VoiceSampleRepository;
use App\Security\TemplateCipher;
use App\Voice\Engine\VoiceEngineException;
use App\Voice\Engine\VoiceEngineInterface;
use App\Voice\EnrollmentPhraseProvider;
use App\Voice\VoiceErrorTranslator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EnrollmentController extends AbstractController
{
    private const MAX_AUDIO_BYTES = 32 * 1024 * 1024;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly VoiceErrorTranslator $errors,
        private readonly EnrollmentPhraseProvider $phraseProvider,
    ) {
    }

    #[Route('/register/voice', name: 'app_register_voice', methods: ['GET'])]
    public function index(VoiceSampleRepository $samples): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isEnrolled()) {
            return $this->redirectToRoute('app_home');
        }

        $phrases = $this->phraseProvider->ensureForUser($user);

        $existing = [];
        foreach ($samples->findForUser($user) as $sample) {
            $existing[$sample->getPhraseIndex()] = true;
        }

        $nextIndex = 1;
        foreach ($phrases as $index => $phrase) {
            if (!isset($existing[$index])) {
                $nextIndex = $index;
                break;
            }
        }

        return $this->render('enrollment/index.html.twig', [
            'phrases' => $phrases,
            'existing' => $existing,
            'nextIndex' => $nextIndex,
        ]);
    }

    #[Route('/api/enroll', name: 'api_enroll', methods: ['POST'])]
    public function enroll(
        Request $request,
        VoiceSampleRepository $samples,
        VoiceEngineInterface $engine,
        TemplateCipher $cipher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'auth_required'], 401);
        }

        if (!$this->isCsrfTokenValid('aurix', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'invalid_csrf'], 403);
        }

        $phrases = $this->phraseProvider->ensureForUser($user);
        $index = (int) $request->request->get('phraseIndex', 0);

        if (!isset($phrases[$index])) {
            return $this->json(['error' => 'invalid_phrase'], 422);
        }

        $phrase = $phrases[$index];
        $audio = $request->files->get('audio');

        if (!$audio instanceof UploadedFile || !$audio->isValid()) {
            return $this->json(['error' => 'missing_audio', 'message' => 'Brak pliku audio.'], 400);
        }

        if ($audio->getSize() > self::MAX_AUDIO_BYTES) {
            return $this->json(['error' => 'audio_too_large', 'message' => 'Plik audio jest zbyt duży.'], 413);
        }

        try {
            $check = $engine->checkPhrase($audio->getPathname(), $phrase);
        } catch (VoiceEngineException $exception) {
            $this->logger->error('Enrollment phrase check failed.', ['exception' => $exception]);
            $status = $exception->statusCode >= 400 && $exception->statusCode < 500 ? $exception->statusCode : 502;

            return $this->json([
                'error' => 'engine_error',
                'message' => 502 === $status ? 'Silnik weryfikacji jest chwilowo niedostępny.' : $this->errors->translate($exception->getMessage()),
            ], $status);
        } catch (\Throwable $exception) {
            $this->logger->error('Enrollment phrase check failed.', ['exception' => $exception]);

            return $this->json([
                'error' => 'engine_unavailable',
                'message' => 'Silnik weryfikacji jest chwilowo niedostępny.',
            ], 502);
        }

        if (!$check->accepted) {
            return $this->json([
                'error' => 'phrase_mismatch',
                'phraseMatch' => $check->phraseMatch,
                'transcript' => $check->transcript,
                'message' => 'Nie rozpoznaliśmy frazy. Spróbuj ponownie.',
            ], 422);
        }

        $sample = $samples->findOneByUserAndIndex($user, $index);

        if (null === $sample) {
            $sample = (new VoiceSample())->setUser($user)->setPhraseIndex($index);
            $entityManager->persist($sample);
        }

        $sample->setPhrase($phrase);
        $sample->setMimeType($audio->getClientMimeType() ?: 'audio/webm');
        $sample->setAudioCiphertext($cipher->encrypt((string) file_get_contents($audio->getPathname())));

        $entityManager->flush();

        $stored = $samples->findForUser($user);
        $completed = count($stored) >= 3;

        if ($completed) {
            $user->setEnrolledAt(new \DateTimeImmutable());
            $entityManager->flush();
            $request->getSession()->set('voice_verified', true);
        }

        $nextIndex = null;
        foreach (array_keys($phrases) as $candidate) {
            if (null === $samples->findOneByUserAndIndex($user, $candidate)) {
                $nextIndex = $candidate;
                break;
            }
        }

        return $this->json([
            'status' => 'ok',
            'phraseIndex' => $index,
            'phraseMatch' => $check->phraseMatch,
            'nextIndex' => $nextIndex,
            'nextPhrase' => null === $nextIndex ? null : $phrases[$nextIndex],
            'completed' => $completed,
        ]);
    }
}
