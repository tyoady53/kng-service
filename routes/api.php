<?php

use App\Http\Controllers\ApiCloudController;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get_data', [ApiController::class, 'get_data']);

Route::prefix('public')->group(function () {

});

Route::prefix('local')->group(function () {
    Route::get('/info', ApiController::class);
    Route::get('/decrypt', [ApiController::class, 'decrypt']);
});

Route::prefix('cloud')->group(function () {
    Route::post('/post_data', [ApiCloudController::class, 'post_data']);
});
