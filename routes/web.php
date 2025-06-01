<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\GNewsController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\LocationFareController;
use App\Http\Controllers\FareController;
use App\Http\Controllers\TomTomController;
use App\Http\Controllers\FoursquareController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('api')->group(function () {

    // --- Geocoding Routes (LocationIQ) ---
    Route::get('/geocode/{address?}', [LocationController::class, 'geocode']);
    // Removed duplicate '/geocode' route. The '{address?}' makes the parameter optional.

    // --- Weather Routes (OpenWeatherMap) ---
    Route::get('/weather', [WeatherController::class, 'index']);

    // --- News Routes (GNews) ---
    // Prefer a single, clear endpoint for search. The controller can handle parameter variations.
    Route::get('/gnews/search/{query?}', [GNewsController::class, 'search'])->name('gnews.search');
    // Removed 'search-direct' as 'search' should be flexible enough.

    // --- Places Routes (Foursquare) ---
    Route::get('/foursquare/search/{query?}', [FoursquareController::class, 'search'])->name('foursquare.search');
    // Removed 'search-direct'. The 'search' method should be the primary entry point.

    // --- TomTom Routes for Maps and Geocoding ---
    Route::get('/tomtom/search', [TomTomController::class, 'search'])->name('tomtom.search');
    Route::get('/tomtom/route', [TomTomController::class, 'getRoute'])->name('tomtom.route');
    // Activated the '/tomtom/route' based on your comment.

    // --- Authentication Routes ---
    Route::post('/register', RegisterController::class)->name('register');
    // Added a name for clarity.

    // --- Fare & Info Calculation Routes ---
    Route::get('/fare-info', [LocationFareController::class, 'getFareAndInfo'])->name('fare.info');

    // For fare calculation, POST is generally preferred for submitting data.
    Route::post('/calculate-fare', [LocationFareController::class, 'calculateFare'])->name('fare.calculate');
    // Consolidated to a single POST route for `calculate-fare`. If GET is strictly needed for
    // simple, idempotent queries, you could add it back, but POST is robust.

    Route::put('/fare/{id}', [LocationFareController::class, 'updateFare'])->name('fare.update');
    Route::patch('/fare/{id}', [LocationFareController::class, 'updateFare']);
    Route::delete('/fare/{id}', [LocationFareController::class, 'deleteFare'])->name('fare.delete');
    // Added `{id}` to fare update/delete routes, which is RESTful for targeting a specific resource.
    // Assuming `updateFare` and `deleteFare` methods will expect an ID.

    Route::get('/fare/calculate', [FareController::class, 'calculate'])->name('fare.specific.calculate');
    // Renamed to avoid collision/confusion with /calculate-fare if both are distinct.

    // --- Authenticated User Routes (Requires Sanctum) ---
    Route::middleware('auth:sanctum')->group(function () {
        // Get authenticated user's details
        Route::get('/user', function (Request $request) {
            return $request->user(); // Returns the authenticated user model
        })->name('user.profile');

        // Another protected GET resource example
        Route::get('/protected-resource', function () {
            return response()->json(['data' => 'This is protected data.']);
        })->name('protected.resource');

        // User Resource Routes (Protected - for managing 'User' records)
        Route::apiResource('users', UserController::class);
        // This single line (Route::apiResource) defines standard RESTful routes for 'users'.
    });
});