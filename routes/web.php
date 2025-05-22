<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GNewsController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Http\Request; 

// XANNNN
Route::prefix('api')->group(function () {
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::post('/geocode', [LocationController::class, 'geocode']);
    Route::get('/geocode/{address}', [LocationController::class, 'geocode']);

    // News Route
    // Assuming NewsController exists and is imported
    Route::get('/news/{country?}', [NewsController::class, 'index']);

    // Krylle weather
    Route::get('/weather', [WeatherController::class, 'index']);
    Route::post('/weather', [WeatherController::class, 'index']);

    // K news
    Route::get('/gnews/{query?}', [GNewsController::class, 'search']);

    // K calendar
    Route::get('/calendar/auth', [CalendarController::class, 'redirectToGoogle']);
    Route::get('/calendar/callback', [CalendarController::class, 'handleGoogleCallback']);
    Route::get('/calendar/events', [CalendarController::class, 'listEvents']);

    //X user login
    Route::post('/register', RegisterController::class);

    Route::middleware('auth:sanctum')->group(function () {
        // Get authenticated user's details
        Route::get('/user', function (Request $request) {
            return $request->user(); // Returns the authenticated user model
        });

        // Another protected GET resource
        Route::get('/protected-resource', function () {
            return response()->json(['data' => 'This is protected data.']);
        });
    });
});

// A standard web route, unrelated to your API
Route::get('/', function () {
    return view('welcome');
});