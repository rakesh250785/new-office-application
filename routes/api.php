<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->get('/ping', fn () => ['pong' => true]);

// ✅ Load your custom admin routes
require base_path('routes/admin_api.php');
