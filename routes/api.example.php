<?php

use App\Http\Controllers\Auth\GoogleMobileAuthController;
use Illuminate\Support\Facades\Route;

// Merge these routes into routes/api.php.
Route::post('/mobile-auth/exchange', [GoogleMobileAuthController::class, 'exchange'])
    ->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [GoogleMobileAuthController::class, 'me']);
    Route::post('/logout', [GoogleMobileAuthController::class, 'logout']);
});
