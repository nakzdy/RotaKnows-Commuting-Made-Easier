<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// You can keep the default Laravel API user route if you wish,
// or remove it if you are managing users through the web.php file's API group.
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
