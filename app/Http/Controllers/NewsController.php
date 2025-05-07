<?php

namespace App\Http\Controllers;

use App\Services\NewsApiService;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct(NewsApiService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function index()
    {
        $news = $this->newsService->getTopHeadlines('ph'); // localized to Philippines
        return view('news.index', ['news' => $news['articles']]);
    }
}
