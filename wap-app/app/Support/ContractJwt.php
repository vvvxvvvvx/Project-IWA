<?php

namespace App\Support;

class ContractJwt
{
    public static function issue(array $payload, string $secret, int $ttlSeconds = 3600): string
    {
        $now = time();
        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => $payload['jti'] ?? bin2hex(random_bytes(16)),
        ]);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            self::base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public static function decode(string $jwt, string $secret): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $signingInput = $headerB64 . '.' . $payloadB64;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        if (! hash_equals($expected, $signatureB64)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (! is_array($payload)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
