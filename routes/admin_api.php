<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentication\AuthenticationController;

# Public route
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthenticationController::class, 'apiLogin']);
});

# Aiuth route
Route::prefix('admin')->middleware('auth::api')->group(function () {
    Route::post('/logout', [AuthenticationController::class, 'apiLogout']);
});

