<?php

declare(strict_types=1);

namespace App\Voice\Engine;

use App\Voice\PhraseMatcher;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Calls the internal Python service over HTTP.
 *
 * The service returns raw scores. The accept/deny policy lives here, except
 * for PAD: AASIST is diagnostic until it is calibrated, so it never blocks.
 */
final class HttpVoiceEngine implements VoiceEngineInterface
{
    private const SIMILARITY_THRESHOLD = 0.80;
    private const PHRASE_THRESHOLD = 0.80;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly PhraseMatcher $matcher,
        #[Autowire('%env(VOICE_ML_URL)%')]
        private readonly string $baseUrl,
        #[Autowire('%env(VOICE_ML_TOKEN)%')]
        private readonly string $token,
    ) {
    }

    public function verify(
        string $audioPath,
        string $expectedPhrase,
        ?string $referenceAudioPath = null,
    ): VerifyResult {
        $body = [
            'audio' => fopen($audioPath, 'r'),
            'expected_phrase' => $expectedPhrase,
        ];

        if (null !== $referenceAudioPath) {
            $body['reference_audio'] = fopen($referenceAudioPath, 'r');
        }

        $data = $this->post($body);

        $similarity = (float) ($data['speaker_score'] ?? 0.0);
        $padScore = (float) ($data['pad_score'] ?? 0.0);
        $phraseMatch = (float) ($data['phrase_match'] ?? 0.0);
        $transcript = (string) ($data['transcript'] ?? '');

        if ('' !== $transcript) {
            $phraseMatch = max($phraseMatch, $this->matcher->score($expectedPhrase, $transcript));
        }

        $accepted = $similarity >= self::SIMILARITY_THRESHOLD
            && $phraseMatch >= self::PHRASE_THRESHOLD;

        $reasons = [];

        if (null === $referenceAudioPath || $similarity < self::SIMILARITY_THRESHOLD) {
            $reasons[] = 'similarity below threshold';
        }

        if ($phraseMatch < self::PHRASE_THRESHOLD) {
            $reasons[] = 'phrase mismatch';
        }

        return new VerifyResult(
            accepted: $accepted,
            similarity: $similarity,
            padScore: $padScore,
            phraseMatch: $phraseMatch,
            reason: $accepted ? null : implode(', ', $reasons),
            stub: false,
        );
    }

    public function checkPhrase(string $audioPath, string $expectedPhrase): PhraseCheckResult
    {
        $data = $this->post([
            'audio' => fopen($audioPath, 'r'),
            'expected_phrase' => $expectedPhrase,
        ]);

        $phraseMatch = (float) ($data['phrase_match'] ?? 0.0);
        $transcript = isset($data['transcript']) ? (string) $data['transcript'] : null;

        if (null !== $transcript && '' !== $transcript) {
            $phraseMatch = max($phraseMatch, $this->matcher->score($expectedPhrase, $transcript));
        }

        return new PhraseCheckResult(
            accepted: $phraseMatch >= self::PHRASE_THRESHOLD,
            phraseMatch: $phraseMatch,
            transcript: $transcript,
            quality: isset($data['quality']) && is_array($data['quality']) ? $data['quality'] : null,
            reason: $phraseMatch >= self::PHRASE_THRESHOLD ? null : 'phrase mismatch',
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function post(array $body): array
    {
        $response = $this->client->request(
            'POST',
            rtrim($this->baseUrl, '/').'/v1/verify',
            [
                'headers' => ['X-Internal-Token' => $this->token],
                'body' => $body,
                'timeout' => 180,
            ],
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf('Voice engine returned HTTP %d.', $response->getStatusCode()));
        }

        return $response->toArray();
    }
}
