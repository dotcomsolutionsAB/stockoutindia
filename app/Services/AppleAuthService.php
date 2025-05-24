<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AppleAuthService
{
    private const APPLE_KEYS_URL = 'https://appleid.apple.com/auth/keys';

    /**
     * Verify an Apple ID token (simplified, like Google)
     *
     * @param string $idToken
     * @param string $clientId
     * @return array
     * @throws Exception
     */
    public function verifyAppleToken(string $idToken, string $clientId): array
    {
        try {
            $appleKeys = $this->getAppleKeys();

            $decodedPayload = JWT::decode($idToken, JWK::parseKeySet($appleKeys), ['RS256']);
            $payloadArray = json_decode(json_encode($decodedPayload), true);

            if (($payloadArray['aud'] ?? null) !== $clientId) {
                throw new Exception('Invalid audience.');
            }

            return $payloadArray;
        } catch (Exception $e) {
            \Log::error('Apple Token Verification Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch Apple public keys (cached)
     *
     * @return array
     * @throws Exception
     */
    private function getAppleKeys(): array
    {
        return Cache::remember('apple_keys', 60, function() {
            $response = Http::get(self::APPLE_KEYS_URL);
            if (!$response->successful()) {
                throw new Exception('Failed to fetch Apple public keys.');
            }
            return $response->json();
        });
    }
}
