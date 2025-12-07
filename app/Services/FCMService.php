<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FCMService
{
    protected $projectId;
    protected $fcmUrl;
    protected $serviceAccountCredentials;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID', 'mystudymate-acfbe');
        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        
        // Try to load from JSON string first (for Laravel Cloud), then fall back to file
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
        if ($credentialsJson) {
            $this->serviceAccountCredentials = json_decode($credentialsJson, true);
        } else {
            $credentialsPath = env('FIREBASE_CREDENTIALS_PATH', storage_path('app/mystudymate-acfbe-firebase-adminsdk-fbsvc-2c2e8800a0.json'));
            if (file_exists($credentialsPath)) {
                $this->serviceAccountCredentials = json_decode(file_get_contents($credentialsPath), true);
            }
        }
    }

    /**
     * Get OAuth2 access token from service account
     */
    private function getAccessToken()
    {
        try {
            if (!$this->serviceAccountCredentials) {
                Log::error('FCM: Service account credentials not found');
                return null;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $this->serviceAccountCredentials
            );

            $token = $credentials->fetchAuthToken();
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('FCM: Failed to get access token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Send push notification via FCM API V1
     * 
     * @param string $fcmToken Device FCM token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @return bool Success status
     */
    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        if (empty($fcmToken)) {
            Log::error('FCM: Token is empty');
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('FCM: Failed to get access token');
            return false;
        }

        // FCM API V1 message format
        $message = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'webpush' => [
                    'notification' => [
                        'icon' => '/icon.png',
                    ],
                    'fcm_options' => [
                        'link' => '/',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $message);

            if ($response->successful()) {
                Log::info('FCM: Notification sent successfully', [
                    'title' => $title,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('FCM: Failed to send notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM: Exception occurred', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple devices (batch)
     * Note: API V1 requires individual requests for each token
     */
    public function sendToMultiple(array $fcmTokens, $title, $body, $data = [])
    {
        if (empty($fcmTokens)) {
            return false;
        }

        $successCount = 0;
        foreach ($fcmTokens as $token) {
            if ($this->sendNotification($token, $title, $body, $data)) {
                $successCount++;
            }
        }

        Log::info('FCM: Batch send completed', [
            'total' => count($fcmTokens),
            'success' => $successCount,
        ]);

        return $successCount > 0;
    }
}
