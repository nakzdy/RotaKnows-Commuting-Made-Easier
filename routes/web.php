<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GNewsController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\LocationFareController;
use Illuminate\Http\Request;

// XANNNN
Route::prefix('api')->group(function () {
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::post('/geocode', [LocationController::class, 'geocode']);
    Route::get('/geocode/{address}', [LocationController::class, 'geocode']);


    // Krylle weather
    Route::get('/weather', [WeatherController::class, 'index']);
    Route::post('/weather', [WeatherController::class, 'index']);

    // K news
    Route::get('/gnews/{query?}', [GNewsController::class, 'search']);

    // K calendar
    Route::get('/calendar/auth', [CalendarController::class, 'redirectToGoogle']);
    Route::get('/calendar/callback', [CalendarController::class, 'handleGoogleCallback']);
    Route::get('/calendar/events', [CalendarController::class, 'listEvents']);

    //X user login (registration)
    Route::post('/register', RegisterController::class);

    //X TomTom
    Route::get('/tomtom/search', [MapController::class, 'searchPlaces'])->name('tomtom.search');
    Route::get('/tomtom/route', [MapController::class, 'getRoute'])->name('tomtom.route');

    //X Combined API
    Route::get('/fare-info', [LocationFareController::class, 'getFareAndInfo']);


    Route::middleware('auth:sanctum')->group(function () {
        // Get authenticated user's details
        Route::get('/user', function (Request $request) {
            return $request->user(); // Returns the authenticated user model
        });

        // Another protected GET resource
        Route::get('/protected-resource', function () {
            return response()->json(['data' => 'This is protected data.']);
        });


        // USER RESOURCE ROUTES (Protected - Moved INSIDE the middleware group)
        Route::apiResource('users', UserController::class);

        // Note on Route::apiResource('users', UserController::class):
        // This single line defines the following routes automatically, all protected by 'auth:sanctum':
        // GET      /api/users          -> UserController@index (list all users)
        // POST     /api/users          -> UserController@store (create a new user) - use this if you want to merge registration here
        // GET      /api/users/{user}   -> UserController@show (show a specific user by ID)
        // PUT/PATCH /api/users/{user}  -> UserController@update (update a specific user by ID)
        // DELETE   /api/users/{user}   -> UserController@destroy (delete a specific user by ID)
        // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    });
});