<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
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

            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => array_map('strval', $data),
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $message
            );

            return $response->successful();
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
