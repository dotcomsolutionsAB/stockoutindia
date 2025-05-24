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
     * Verify an Apple ID token
     *
     * @param string $idToken The ID token to verify
     * @param string $clientId Your Apple Service ID (Client ID)
     * @return array The decoded payload data
     * @throws Exception If token verification fails
     */
    public function verifyAppleToken(string $idToken, string $clientId): array
    {
        try {
            // Fetch and cache Apple's public keys
            $appleKeys = Cache::remember('apple_keys', 60, function() {
                $response = Http::get(self::APPLE_KEYS_URL);
                if (!$response->successful()) {
                    throw new Exception('Failed to fetch Apple public keys.');
                }
                return $response->json();
            });

            // Correct call to decode (no third parameter)
            $headers = [];
            $decodedPayload = JWT::decode($idToken, JWK::parseKeySet($appleKeys), ['RS256'], $headers);


            // Convert the decoded payload (which is a stdClass) to an array
            $payloadArray = json_decode(json_encode($decodedPayload), true);

            // Validate the audience
            if (($payloadArray['aud'] ?? null) !== $clientId) {
                throw new Exception('Invalid audience.');
            }

            return $payloadArray;

        } catch (Exception $e) {
            \Log::error('Apple Token Verification Failed: ' . $e->getMessage());
            throw $e;
        }
    }

}
