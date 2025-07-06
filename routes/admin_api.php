<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\ExpencesController;

Route::prefix('admin')->middleware('api')->group(function () {
    Route::get('/testApi', [ExpencesController::class, 'testApi']);
});

