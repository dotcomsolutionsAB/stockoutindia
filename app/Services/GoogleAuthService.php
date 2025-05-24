<?php

namespace App\Services;

use Google_Client;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class GoogleAuthService
{
    /**
     * Verify a Google ID token with leeway
     *
     * @param string $idToken The ID token to verify
     * @param string $audience Your Google Client ID
     * @return array The decoded payload data
     * @throws Exception If token verification fails
     */
    public function verifyGoogleToken(string $idToken, string $audience): array
    {
        try {
            // Get Google's public keys for verification
            $certs = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v3/certs'), true);
            $keys = JWK::parseKeySet($certs);

            // Add 60-second leeway
            JWT::$leeway = 60;

            // Decode the JWT (verify signature, expiration, iat, etc.)
            $payload = JWT::decode($idToken, $keys, ['RS256']);

            // Convert payload to array
            $payloadArray = (array)$payload;

            // Validate audience (Google Client ID)
            if ($payloadArray['aud'] !== $audience) {
                throw new Exception('Invalid audience.');
            }

            // Optional: Log iat and current time for debugging
            $issuedAt = $payloadArray['iat'];
            $issuedAtFormatted = \Carbon\Carbon::createFromTimestamp($issuedAt)->setTimezone('Asia/Kolkata')->toDateTimeString();
            \Log::info("Token issued at (iat): $issuedAtFormatted");
            \Log::info("Current server time: " . now()->format('Y-m-d H:i:s T'));

            return $payloadArray;

        } catch (Exception $e) {
            \Log::error('Google Token Verification Failed: ' . $e->getMessage());
            throw new Exception('Token verification failed: ' . $e->getMessage());
        }
    }
}
