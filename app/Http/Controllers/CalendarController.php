<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalendarService;

class CalendarController extends Controller
{
    protected $calendar;

    public function __construct(CalendarService $calendar)
    {
        $this->calendar = $calendar;
    }

    public function redirectToGoogle()
    {
        return redirect($this->calendar->getAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $this->calendar->handleCallback($request->input('code'));
        return redirect('/calendar/events');
    }

    public function listEvents()
    {
        $events = $this->calendar->listEvents();
        return response()->json($events);
    }
}
