<?php

namespace App\Services\GoogleClient\YouTube;

use Google_Client;
use Google_Service_YouTube;

class YouTubeService
{
    protected Google_Client $client;
    protected Google_Service_YouTube $youtube;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey(config('google.api_key'));
        
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    public function getClient(): Google_Client
    {
        return $this->client;
    }

    public function getYouTube(): Google_Service_YouTube
    {
        return $this->youtube;
    }
}