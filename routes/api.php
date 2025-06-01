<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\PlacesController;
use App\Http\Controllers\GNewsController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\LocationFareController;
use App\Http\Controllers\FareController;
use Illuminate\Http\Request;

// LocationIQ Routes for Geocoding
Route::get('/geocode', [LocationController::class, 'geocode']);
Route::get('/geocode/{address}', [LocationController::class, 'geocode']);

// OpenWeatherMap Routes for Weather information
Route::get('/weather', [WeatherController::class, 'index']);

// GNews Routes for news articles
Route::get('/gnews/{query?}', [GNewsController::class, 'search']);

// Foursquare Places Routes for searching nearby places
Route::get('/places', [PlacesController::class, 'search']);

// User Registration (Authentication)
Route::post('/register', RegisterController::class);

// TomTom Routes for map search and route calculation
Route::get('/tomtom/search', [MapController::class, 'searchPlaces'])->name('tomtom.search');
Route::get('/tomtom/route', [MapController::class, 'getRoute'])->name('tomtom.route');

// Combined API for ad-hoc Fare and Info Calculation 
Route::get('/fare-info', [LocationFareController::class, 'getFareAndInfo']);
Route::get('/calculate-fare', [LocationFareController::class, 'calculateFare']);
Route::put('/fare-update', [LocationFareController::class, 'updateFare']);
Route::patch('/fare-update', [LocationFareController::class, 'updateFare']);

// Routes requiring API authentication (using Sanctum middleware)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/protected-resource', function () {
        return response()->json(['data' => 'This is protected data.']);
    });

    Route::apiResource('users', UserController::class);
});