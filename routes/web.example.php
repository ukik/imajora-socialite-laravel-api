<?php

use App\Http\Controllers\Auth\GoogleMobileAuthController;
use Illuminate\Support\Facades\Route;

// Merge these routes into routes/web.php.
// Keep OAuth redirect/callback on the web middleware so Socialite can use sessions.
Route::get('/auth/google/mobile', [GoogleMobileAuthController::class, 'redirect'])
    ->name('auth.google.mobile');
Route::get('/auth/google/callback', [GoogleMobileAuthController::class, 'callback'])
    ->name('auth.google.callback');
