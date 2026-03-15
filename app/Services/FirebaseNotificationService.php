<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    public function send($fcmToken, $title, $body, $type)
    {
        $client = new Client();
        $client->setAuthConfig(config('services.firebase.credentials'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $client->fetchAccessTokenWithAssertion();

        $accessToken = $client->getAccessToken()['access_token'];

        $projectId = json_decode(file_get_contents(config('services.firebase.credentials')), true)['project_id'];

        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $payload = [
            "message" => [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ],
                "data" => [
                    "type" => $type
                ]
            ]
        ];

        $response = Http::withToken($accessToken)
            ->post($url, $payload);

        return $response->json();
    }

    // alias for send()
    public function sendNotification($fcmToken, $title, $body, $type)
    {
        return $this->send($fcmToken, $title, $body, $type);
    }
}
