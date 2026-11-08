<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Encrypts voice templates at rest.
 *
 * For the prototype the key is derived from APP_SECRET. In production use a
 * dedicated key stored outside the repository and rotate it deliberately.
 */
final class TemplateCipher
{
    private readonly string $key;

    public function __construct(#[Autowire('%kernel.secret%')] string $secret)
    {
        $this->key = hash('sha256', $secret, true);
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);

        if (false === $decoded || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Invalid voice template payload.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if (false === $plaintext) {
            throw new \RuntimeException('Unable to decrypt voice template.');
        }

        return $plaintext;
    }
}
