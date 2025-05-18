<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;

class CalendarService
{
    protected $client;
    protected $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(env('GOOGLE_CALENDAR_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
        $this->client->addScope(Calendar::CALENDAR_READONLY);
        $this->client->setAccessType('offline');
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback($code)
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        session(['google_token' => $token]);
    }

    public function listEvents()
    {
        $this->client->setAccessToken(session('google_token'));

        $service = new Calendar($this->client);
        $events = $service->events->listEvents('primary', [
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => now()->toRfc3339String(),
        ]);

        return $events->getItems();
    }
}
