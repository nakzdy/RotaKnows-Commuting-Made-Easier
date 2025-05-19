<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TomTomController;

// XANNNN
Route::prefix('api')->group(function () {
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::post('/geocode', [LocationController::class, 'geocode']);
    Route::get('/geocode/{address}', [LocationController::class, 'geocode']);

    //News Route
    Route::get('/news/{country?}', [NewsController::class, 'index']);

    //krylle weather
    Route::get('/weather', [WeatherController::class, 'index']);                                                                                                                                                                  
});
