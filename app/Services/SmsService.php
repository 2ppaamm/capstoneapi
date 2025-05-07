<?php

namespace App\Services;

use Twilio\Rest\Client;

class SmsService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
    }

    public function send($to, $message)
    {
        $response = $this->client->messages->create($to, [
            'from' => env('TWILIO_FROM'),
            'body' => $message,
        ]);

        \Log::info("Twilio SMS response", [
            'sid' => $response->sid,
            'status' => $response->status,
            'to' => $response->to,
            'from' => $response->from,
            'body' => $response->body,
        ]);

        return $response;
    }
}
