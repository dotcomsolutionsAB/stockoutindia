<?php

namespace App\Services;

use Google_Client;
use Exception;

class GoogleAuthService
{
    /**
     * Verify a Google ID token
     *
     * @param string $idToken The ID token to verify
     * @param string $audience Your Google Client ID
     * @return array The decoded payload data
     * @throws Exception If token verification fails
     */
    public function verifyGoogleToken(string $idToken, string $audience): array
    {
        $client = new Google_Client(['client_id' => $audience]);

        try {
            $payload = $client->verifyIdToken($idToken);

            dd($payload);
            if ($payload) {
                return $payload;
            }

            throw new Exception('Invalid or expired token.');
        } catch (Exception $e) {
            \Log::error('Google Token Verification Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
