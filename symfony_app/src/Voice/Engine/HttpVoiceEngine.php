<?php

declare(strict_types=1);

namespace App\Voice\Engine;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Calls the internal Python service over HTTP.
 *
 * The service returns raw scores; the accept/deny decision stays here so
 * thresholds can be changed without redeploying the models.
 */
final class HttpVoiceEngine implements VoiceEngineInterface
{
    private const SIMILARITY_THRESHOLD = 0.80;
    private const PAD_THRESHOLD = 0.90;
    private const PHRASE_THRESHOLD = 0.80;

    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire('%env(VOICE_ML_URL)%')]
        private readonly string $baseUrl,
        #[Autowire('%env(VOICE_ML_TOKEN)%')]
        private readonly string $token,
    ) {
    }

    public function verify(string $audioPath, string $expectedPhrase): VerifyResult
    {
        $response = $this->client->request(
            'POST',
            rtrim($this->baseUrl, '/').'/v1/verify',
            [
                'headers' => ['X-Internal-Token' => $this->token],
                'body' => [
                    'audio' => fopen($audioPath, 'r'),
                    'expected_phrase' => $expectedPhrase,
                ],
                'timeout' => 120,
            ],
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf('Voice engine returned HTTP %d.', $response->getStatusCode()));
        }

        $data = $response->toArray();

        $similarity = (float) ($data['speaker_score'] ?? 0.0);
        $padScore = (float) ($data['pad_score'] ?? 0.0);
        $phraseMatch = (float) ($data['phrase_match'] ?? 0.0);

        $accepted = $similarity >= self::SIMILARITY_THRESHOLD
            && $padScore >= self::PAD_THRESHOLD
            && $phraseMatch >= self::PHRASE_THRESHOLD;

        return new VerifyResult(
            accepted: $accepted,
            similarity: $similarity,
            padScore: $padScore,
            phraseMatch: $phraseMatch,
            reason: $accepted ? null : $this->rejectionReason($similarity, $padScore, $phraseMatch),
            stub: false,
        );
    }

    private function rejectionReason(float $similarity, float $padScore, float $phraseMatch): string
    {
        $reasons = [];

        if ($similarity < self::SIMILARITY_THRESHOLD) {
            $reasons[] = 'similarity below threshold';
        }

        if ($padScore < self::PAD_THRESHOLD) {
            $reasons[] = 'spoofing suspected';
        }

        if ($phraseMatch < self::PHRASE_THRESHOLD) {
            $reasons[] = 'phrase mismatch';
        }

        return implode(', ', $reasons) ?: 'rejected';
    }
}
