<?php

use App\Http\Controllers\Cofiguration\Notification\NotificationController;
use App\Http\Controllers\Cofiguration\Principal\PrincipalController;
use App\Http\Controllers\Cofiguration\QuotationFormat\QuotationFormatController;
use App\Http\Controllers\Dropdown\DropdownController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendor\Courier\CourierController;
use App\Http\Controllers\Authentication\AuthenticationController;

# Public route
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthenticationController::class, 'apiLogin']);
});

# Aiuth route
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthenticationController::class, 'apiLogout']);

    # Vendor Inside
    Route::post('/addUpdateCourier', [CourierController::class, 'addUpdateCourier']);
    Route::post('/getCourier', [CourierController::class, 'getCourier']);
    Route::post('/deleteCourier', [CourierController::class, 'deleteCourier']);

    # Dropdown route
    Route::post('/getBranchDD', [DropdownController::class, 'getBranchDD']);
    Route::post('/getPrincipalTypeDD', [DropdownController::class, 'getPrincipalTypeDD']);

    # Quotation format
    Route::post('/addUpdateQuotationFormat', [QuotationFormatController::class, 'addUpdateQuotationFormat']);
    Route::post('/getQuotationFormat', [QuotationFormatController::class, 'getQuotationFormat']);
    Route::post('/deleteQuotationFormat', [QuotationFormatController::class, 'deleteQuotationFormat']);

    # Quotation format
    Route::post('/addUpdateNotification', [NotificationController::class, 'addUpdateNotification']);
    Route::post('/getNotification', [NotificationController::class, 'getNotification']);
    Route::post('/deleteNotification', [NotificationController::class, 'deleteNotification']);
    

    


});

