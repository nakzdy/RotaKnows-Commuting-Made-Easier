<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GNewsService;

class GNewsController extends Controller
{
    protected $gnews;

    public function __construct(GNewsService $gnews)
    {
        $this->gnews = $gnews;
    }

    public function search($query = 'traffic Philippines')
    {
        return response()->json($this->gnews->searchNews($query));
    }

    public function searchDirect($query = 'traffic Philippines')
    {
        $gnews = new GNewsService();
        $result = $gnews->searchNews($query);

        return response()->json($result);
    }
}
