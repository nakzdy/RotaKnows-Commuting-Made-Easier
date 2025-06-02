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
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Basic web route
Route::get('/', function () {
    return view('welcome'); 
});


Route::prefix('api')->group(function () {

    // --- Geocoding Routes (LocationIQ) ---
    Route::get('/geocode/{address?}', [LocationController::class, 'geocode'])->name('geocode');

    // --- Weather Routes (OpenWeatherMap) ---
    Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');

    // --- News Routes (GNews) ---
    Route::get('/gnews/search/{query?}', [GNewsController::class, 'search'])->name('gnews.search');

    // --- Foursquare Places Routes ---
    Route::get('/foursquare/search/{query?}', [PlacesController::class, 'search'])->name('foursquare.search');


    // --- TomTom Routes for map search and route calculation ---
    Route::get('/tomtom/search', [MapController::class, 'searchPlaces'])->name('tomtom.search');
    Route::get('/tomtom/route', [MapController::class, 'getRoute'])->name('tomtom.route');


    // --- User Registration (Authentication) ---
    Route::post('/register', RegisterController::class)->name('register');


    // --- Fare & Info Calculation Routes (Handled by LocationFareController) ---
    Route::get('/fare-info', [LocationFareController::class, 'getFareAndInfo'])->name('fare.info');
    Route::get('/calculate-fare', [LocationFareController::class, 'calculateFare'])->name('fare.calculate');

    // RESTful update/delete for fare resources using an ID
    Route::put('/fare/{id}', [LocationFareController::class, 'updateFare'])->name('fare.update');
    Route::patch('/fare/{id}', [LocationFareController::class, 'updateFare']);
    Route::delete('/fare/{id}', [LocationFareController::class, 'deleteFare'])->name('fare.delete');


    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('user.profile');

        Route::get('/protected-resource', function () {
            return response()->json(['data' => 'This is protected data.']);
        })->name('protected.resource');

        Route::apiResource('users', UserController::class);
    });

});