<?php

use App\Http\Controllers\Cofiguration\Notification\NotificationController;
use App\Http\Controllers\Cofiguration\Principal\PrincipalController;
use App\Http\Controllers\Cofiguration\QuotationFormat\QuotationFormatController;
use App\Http\Controllers\Dropdown\DropdownController;
use App\Http\Controllers\Product\Category\CategoryController;
use App\Http\Controllers\Product\Parameter\ParameterController;
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
    Route::post('/getParameterFieldDD', [DropdownController::class, 'getParameterFieldDD']);

    # Quotation format
    Route::post('/addUpdateQuotationFormat', [QuotationFormatController::class, 'addUpdateQuotationFormat']);
    Route::post('/getQuotationFormat', [QuotationFormatController::class, 'getQuotationFormat']);
    Route::post('/deleteQuotationFormat', [QuotationFormatController::class, 'deleteQuotationFormat']);

    # Notification
    Route::post('/addUpdateNotification', [NotificationController::class, 'addUpdateNotification']);
    Route::post('/getNotification', [NotificationController::class, 'getNotification']);
    Route::post('/deleteNotification', [NotificationController::class, 'deleteNotification']);

    # Principal
    Route::post('/addUpdatePrincipal', [PrincipalController::class, 'addUpdatePrincipal']);
    Route::post('/getPrincipal', [PrincipalController::class, 'getPrincipal']);
    Route::post('/deletePrincipal', [PrincipalController::class, 'deletePrincipal']);

    # Parameter
    Route::post('/addUpdateParameter', [ParameterController::class, 'addUpdateParameter']);
    Route::post('/getParameter', [ParameterController::class, 'getParameter']);
    Route::post('/deleteParameter', [ParameterController::class, 'deleteParameter']);

    # Category
    Route::post('/addUpdateCategory', [CategoryController::class, 'addUpdateCategory']);
    Route::post('/getCategory', [CategoryController::class, 'getCategory']);
    Route::post('/deleteCategory', [CategoryController::class, 'deleteCategory']);


});

