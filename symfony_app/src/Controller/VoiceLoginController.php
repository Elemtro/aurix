<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\VoiceSampleRepository;
use App\Security\TemplateCipher;
use App\Voice\Engine\VoiceEngineException;
use App\Voice\Engine\VoiceEngineInterface;
use App\Voice\VoiceErrorTranslator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoiceLoginController extends AbstractController
{
    private const MAX_AUDIO_BYTES = 32 * 1024 * 1024;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly VoiceErrorTranslator $errors,
    ) {
    }

    #[Route('/login/voice', name: 'app_login_voice', methods: ['GET'])]
    public function index(Request $request, VoiceSampleRepository $samples): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isEnrolled()) {
            return $this->redirectToRoute('app_register_voice');
        }

        if ($request->getSession()->get('voice_verified', false)) {
            return $this->redirectToRoute('app_home');
        }

        $all = $samples->findForUser($user);

        if (count($all) < 3) {
            return $this->redirectToRoute('app_register_voice');
        }

        $sample = $all[random_int(0, count($all) - 1)];
        $request->getSession()->set('voice_login_sample_id', $sample->getId());

        return $this->render('security/voice_login.html.twig', [
            'phrase' => $sample->getPhrase(),
            'attempts' => (int) $request->getSession()->get('voice_login_attempts', 0),
            'maxAttempts' => self::MAX_ATTEMPTS,
        ]);
    }

    #[Route('/api/login/voice', name: 'api_login_voice', methods: ['POST'])]
    public function verify(
        Request $request,
        VoiceSampleRepository $samples,
        VoiceEngineInterface $engine,
        TemplateCipher $cipher,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'auth_required'], 401);
        }

        if (!$this->isCsrfTokenValid('aurix', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'invalid_csrf'], 403);
        }

        $sampleId = (int) $request->getSession()->get('voice_login_sample_id', 0);
        $request->getSession()->remove('voice_login_sample_id');

        if (0 === $sampleId) {
            return $this->json([
                'error' => 'challenge_expired',
                'message' => 'Fraza wygasła. Odśwież stronę.',
            ], 410);
        }

        $sample = $samples->find($sampleId);

        if (null === $sample || $sample->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'invalid_sample'], 410);
        }

        $audio = $request->files->get('audio');

        if (!$audio instanceof UploadedFile || !$audio->isValid()) {
            return $this->json(['error' => 'missing_audio', 'message' => 'Brak pliku audio.'], 400);
        }

        if ($audio->getSize() > self::MAX_AUDIO_BYTES) {
            return $this->json(['error' => 'audio_too_large', 'message' => 'Plik audio jest zbyt duży.'], 413);
        }

        $extension = match ($sample->getMimeType()) {
            'audio/mp4', 'audio/m4a', 'audio/x-m4a' => 'm4a',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            default => 'webm',
        };

        $referencePath = sys_get_temp_dir().'/aurix_ref_'.bin2hex(random_bytes(6)).'.'.$extension;

        try {
            file_put_contents($referencePath, $cipher->decrypt($sample->getAudioCiphertext()));
            $result = $engine->verify($audio->getPathname(), $sample->getPhrase(), $referencePath);
        } catch (VoiceEngineException $exception) {
            $this->logger->error('Voice login failed.', ['exception' => $exception]);
            $status = $exception->statusCode >= 400 && $exception->statusCode < 500 ? $exception->statusCode : 502;

            return $this->json([
                'error' => 'engine_error',
                'message' => 502 === $status ? 'Silnik weryfikacji jest chwilowo niedostępny.' : $this->errors->translate($exception->getMessage()),
            ], $status);
        } catch (\Throwable $exception) {
            $this->logger->error('Voice login failed.', ['exception' => $exception]);

            return $this->json([
                'error' => 'engine_unavailable',
                'message' => 'Silnik weryfikacji jest chwilowo niedostępny.',
            ], 502);
        } finally {
            @unlink($referencePath);
        }

        if (!$result->accepted) {
            $attempts = (int) $request->getSession()->get('voice_login_attempts', 0) + 1;
            $request->getSession()->set('voice_login_attempts', $attempts);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $request->getSession()->invalidate();

                return $this->json([
                    'error' => 'too_many_attempts',
                    'message' => 'Zbyt wiele prób. Zaloguj się ponownie.',
                ], 429);
            }

            return $this->json([
                'error' => 'verification_failed',
                'accepted' => false,
                'similarity' => $result->similarity,
                'phraseMatch' => $result->phraseMatch,
                'padScore' => $result->padScore,
                'reason' => $result->reason,
                'attemptsLeft' => self::MAX_ATTEMPTS - $attempts,
                'message' => 'Nie udało się zweryfikować głosu. Spróbuj ponownie.',
            ], 422);
        }

        $request->getSession()->set('voice_verified', true);
        $request->getSession()->set('voice_login_attempts', 0);
        $request->getSession()->getFlashBag()->add('voice_result', [
            'similarity' => $result->similarity,
            'phraseMatch' => $result->phraseMatch,
            'padScore' => $result->padScore,
        ]);

        return $this->json([
            'accepted' => true,
            'similarity' => $result->similarity,
            'phraseMatch' => $result->phraseMatch,
            'padScore' => $result->padScore,
        ]);
    }
}
