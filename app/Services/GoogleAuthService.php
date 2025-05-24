<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;
use Illuminate\Support\Facades\Http;

class GoogleAuthService
{
    private const GOOGLE_KEYS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    /**
     * Verify a Google ID token with leeway
     *
     * @param string $idToken The ID token to verify
     * @param string $clientId Your Google Client ID (audience)
     * @return array The decoded payload data
     * @throws Exception If token verification fails
     */
    public function verifyGoogleToken(string $idToken, string $clientId): array
    {
        try {
            // Fetch Google's public keys (optional cache can be added)
            $response = Http::get(self::GOOGLE_KEYS_URL);
            if (!$response->successful()) {
                throw new Exception('Failed to fetch Google public keys.');
            }
            $googleKeys = $response->json();

            // Set leeway in seconds (e.g., 60 seconds)
            JWT::$leeway = 60;

            \Log::info('Google Sign In About to call JWT::decode', [
                'idToken' => $idToken,
                'keys' => $googleKeys,
                'algs' => ['RS256']
            ]);

            // Decode JWT with JWK
            $decodedPayload = JWT::decode($idToken, JWK::parseKeySet($googleKeys));
            $payloadArray = json_decode(json_encode($decodedPayload), true);

            // Validate audience (client ID)
            if ($payloadArray) {
                return $payloadArray;
            }

            throw new Exception('Invalid or expired token.');
        } catch (Exception $e) {
            \Log::error('Google Token Verification Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
