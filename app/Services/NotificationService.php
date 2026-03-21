<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private string $projectId;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID');
    }

    private function getAccessToken(): string
    {
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            json_decode($credentialsJson, true)
        );

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }

    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            $message = json_encode([
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => array_map('strval', $data),
                ]
            ]);

            $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close supprimé ✅

            if ($httpCode !== 200) {
                Log::error('FCM Error response: ' . $response);
            }

            return $httpCode === 200;
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendToAll(string $title, string $body, array $data = []): void
    {
        $users = \App\Models\User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            $this->sendToDevice($user->fcm_token, $title, $body, $data);
        }
    }
}
