<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NewsApiService
{
    protected $apiKey;
    protected $baseUrl = 'https://newsapi.org/v2';

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key');
    }

    public function getTopHeadlines($country = 'ph') // 'ph' for Philippines
    {
        $response = Http::get("{$this->baseUrl}/top-headlines", [
            'country' => $country,
            'apiKey' => $this->apiKey,
        ]);

        return $response->json();
    }
}