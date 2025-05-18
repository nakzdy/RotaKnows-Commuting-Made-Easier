<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\GNewsController;

// XANNNN
Route::prefix('api')->group(function () {
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::post('/geocode', [LocationController::class, 'geocode']);
    Route::get('/geocode/{address}', [LocationController::class, 'geocode']);

    //k news
    Route::get('/gnews/{query?}', [GNewsController::class, 'search']);

    //krylle weather
    Route::get('/weather', [WeatherController::class, 'index']);
    Route::post('/weather', [WeatherController::class, 'index']);

    //k calendar
    Route::get('/calendar/auth', [CalendarController::class, 'redirectToGoogle']);
    Route::get('/calendar/callback', [CalendarController::class, 'handleGoogleCallback']);
    Route::get('/calendar/events', [CalendarController::class, 'listEvents']);
});
