<?php

declare(strict_types=1);

namespace Framework\Component\Live;

/**
 * HMAC-SHA256 signing helper for Live component state integrity.
 *
 * Extracted from HasState to provide a single, testable signing point.
 * The signing key is derived from config('app.key') using HMAC-SHA256
 * with a purpose-specific salt, preventing key reuse across subsystems.
 */
class Checksum
{
    /**
     * Sign an arbitrary string payload with HMAC-SHA256.
     */
    public static function sign(string $payload, string $context = 'state'): string
    {
        return hash_hmac('sha256', $context . $payload, self::signingKey(), true);
    }

    /**
     * Verify a signature against a payload (timing-safe).
     */
    public static function verify(string $payload, string $signature, string $context = 'state'): bool
    {
        $expected = self::sign($payload, $context);
        return hash_equals($expected, $signature);
    }

    /**
     * Sign a data array, producing a deterministic checksum string.
     *
     * Used for per-property checksums (locked_checksums) and overall
     * state integrity verification.
     */
    public static function checksum(array $data): string
    {
        self::recursiveNormalize($data);
        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * Derive the signing key from the application key.
     */
    public static function signingKey(): string
    {
        $appKey = config('app.key', 'default-key');
        return hash_hmac('sha256', 'live-component-state', $appKey);
    }

    /**
     * Serialize + compress + sign a state payload, returning base64.
     */
    public static function seal(string $componentClass, string $serialized): string
    {
        $compressed = function_exists('gzcompress') ? gzcompress($serialized) : $serialized;
        $sig = self::sign($componentClass . $compressed, 'state');
        return base64_encode($sig . $compressed);
    }

    /**
     * Verify + decompress + deserialize a state payload.
     *
     * @throws \RuntimeException on signature failure
     * @return string The decompressed serialized data
     */
    public static function unseal(string $componentClass, string $sealed): string
    {
        $decoded = base64_decode($sealed, true);
        if (!$decoded || strlen($decoded) < 32) {
            throw new \RuntimeException('Live component state payload is invalid.');
        }

        $sig = substr($decoded, 0, 32);
        $compressed = substr($decoded, 32);

        $expectedSig = self::sign($componentClass . $compressed, 'state');
        if (!hash_equals($expectedSig, $sig)) {
            throw new \RuntimeException('Live component state signature verification failed. Possible tampering detected.');
        }

        return function_exists('gzuncompress') ? gzuncompress($compressed) : $compressed;
    }

    /**
     * Normalize an array for deterministic checksum generation.
     */
    private static function recursiveNormalize(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursiveNormalize($value);
            } elseif ($value !== null) {
                $value = (string) $value;
            }
        }
    }
}
