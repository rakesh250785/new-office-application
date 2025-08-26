<?php

use App\Http\Controllers\Authentication\ProfileController;
use App\Http\Controllers\ClientUser\Customer\CustomerController;
use App\Http\Controllers\ClientUser\Owner\OwnerController;
use App\Http\Controllers\ClientUser\User\UserController;
use App\Http\Controllers\Configuration\Notification\NotificationController;
use App\Http\Controllers\Configuration\Principal\PrincipalController;
use App\Http\Controllers\Configuration\QuotationFormat\QuotationFormatController;
use App\Http\Controllers\Configuration\Reason\ReasonController;
use App\Http\Controllers\Dashboard\QuotationSummaryController;
use App\Http\Controllers\Product\Brand\BrandController;
use App\Http\Controllers\Product\Product\ProductController;
use App\Http\Controllers\Product\USP\UspController;
use App\Http\Controllers\SaleInsight\Invoice\InvoiceController;
use App\Http\Controllers\SaleInsight\Order\FullOrderController;
use App\Http\Controllers\SaleInsight\Order\PartialOrderController;
use App\Http\Controllers\SaleInsight\Quotation\QuotationDetailController;
use App\Http\Controllers\Vendor\Source\SourceController;
use App\Http\Controllers\Dropdown\DropdownController;
use App\Http\Controllers\Product\Category\CategoryController;
use App\Http\Controllers\Product\Parameter\ParameterController;
use App\Http\Controllers\Vendor\Supplier\SupplierController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendor\Courier\CourierController;
use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Controllers\ClientUser\RolePermission\RolePermissionController;


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
    Route::post('/getUpsCategoryDD', [DropdownController::class, 'getUpsCategoryDD']);
    Route::post('/getPrincipalDD', [DropdownController::class, 'getPrincipalDD']);
    Route::post('/getCountryDD', [DropdownController::class, 'getCountryDD']);
    Route::post('/getStateDD', [DropdownController::class, 'getStateDD']);
    Route::post('/getOwnerDD', [DropdownController::class, 'getOwnerDD']);
    Route::post('/getClassificationDD', [DropdownController::class, 'getClassificationDD']);
    Route::post('/getBrandDD', [DropdownController::class, 'getBrandDD']);
    Route::post('/getCategoryDD', [DropdownController::class, 'getCategoryDD']);
    Route::post('/getProductDD', [DropdownController::class, 'getProductDD']);
    Route::post('/getSourceDD', [DropdownController::class, 'getSourceDD']);
    Route::post('/getCurrencyDD', [DropdownController::class, 'getCurrencyDD']);
    Route::post('/getQuotationTypeDD', [DropdownController::class, 'getQuotationTypeDD']);
    Route::post('/getCompanyDD', [DropdownController::class, 'getCompanyDD']);
    Route::post('/getNotificationDD', [DropdownController::class, 'getNotificationDD']);
    Route::post('/getPaymentAdvanceDD', [DropdownController::class, 'getPaymentAdvanceDD']);
    Route::post('/getStatusDD', [DropdownController::class, 'getStatusDD']);
    Route::post('/getCourierDD', [DropdownController::class, 'getCourierDD']);
    Route::post('/getRoleDD', [DropdownController::class, 'getRoleDD']);
    Route::post('/getOrderStatusDD', [DropdownController::class, 'getOrderStatusDD']);

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

    # Source
    Route::post('/addUpdateSource', [SourceController::class, 'addUpdateSource']);
    Route::post('/getSource', [SourceController::class, 'getSource']);
    Route::post('/deleteSource', [SourceController::class, 'deleteSource']);

    # Brand
    Route::post('/addUpdateBrand', [BrandController::class, 'addUpdateBrand']);
    Route::post('/getBrand', [BrandController::class, 'getBrand']);
    Route::post('/deleteBrand', [BrandController::class, 'deleteBrand']);

    # Reason
    Route::post('/addUpdateReason', [ReasonController::class, 'addUpdateReason']);
    Route::post('/getReason', [ReasonController::class, 'getReason']);
    Route::post('/deleteReason', [ReasonController::class, 'deleteReason']);

    # USP
    Route::post('/addUpdateUSP', [UspController::class, 'addUpdateUSP']);
    Route::post('/getUSP', [UspController::class, 'getUSP']);
    Route::post('/deleteUSP', [UspController::class, 'deleteUSP']);

    # Owner
    Route::post('/addUpdateOwner', [OwnerController::class, 'addUpdateOwner']);
    Route::post('/getOwner', [OwnerController::class, 'getOwner']);
    Route::post('/deleteOwner', [OwnerController::class, 'deleteOwner']);

    # Customer
    Route::post('/addUpdateCustomer', [CustomerController::class, 'addUpdateCustomer']);
    Route::post('/getCustomer', [CustomerController::class, 'getCustomer']);
    Route::post('/deleteCustomer', [CustomerController::class, 'deleteCustomer']);

    # Product
    Route::post('/addUpdateProduct', [ProductController::class, 'addUpdateProduct']);
    Route::post('/getProduct', [ProductController::class, 'getProduct']);
    Route::post('/deleteProduct', [ProductController::class, 'deleteProduct']);

    # Users
    Route::post('/addUpdateUser', [UserController::class, 'addUpdateUser']);
    Route::post('/getUser', [UserController::class, 'getUser']);
    Route::post('/deleteUser', [UserController::class, 'deleteUser']);

    # Supplier
    Route::post('/addUpdateSupplier', [SupplierController::class, 'addUpdateSupplier']);
    Route::post('/getSupplier', [SupplierController::class, 'getSupplier']);
    Route::post('/deleteSupplier', [SupplierController::class, 'deleteSupplier']);

    # Quotation
    Route::post('/addUpdateQuotation', [QuotationDetailController::class, 'addUpdateQuotation']);
    Route::post('/getQuotation', [QuotationDetailController::class, 'getQuotation']);
    Route::post('/deleteQuotation', [QuotationDetailController::class, 'deleteQuotation']);
    Route::post('/updateQuotationStatus', [QuotationDetailController::class, 'updateQuotationStatus']);

    # Order
    Route::post('/storeOrder', [FullOrderController::class, 'storeOrder']);
    Route::post('/getOrder', [FullOrderController::class, 'getOrder']);
    Route::post('/deleteOrder', [FullOrderController::class, 'deleteOrder']);
    Route::post('/addOrderReason', [FullOrderController::class, 'addOrderReason']);


    # Partial rder
    Route::post('/storePartialOrder', [PartialOrderController::class, 'storePartialOrder']);
    Route::post('/getPartialOrder', [PartialOrderController::class, 'getPartialOrder']);
    Route::post('/deletePartialOrder', [PartialOrderController::class, 'deletePartialOrder']);


    # Role and permission
    Route::post('listRolesWithPermissions', [RolePermissionController::class, 'listRolesWithPermissions']);
    Route::post('listPermissions', [RolePermissionController::class, 'listPermissions']);
    Route::post('addUpdateRole', [RolePermissionController::class, 'addUpdateRole']);
    Route::post('deleteRole', [RolePermissionController::class, 'deleteRole']);
    // Route::post('storeRole', [RolePermissionController::class, 'storeRole']);
    // Route::post('deleteRole', [RolePermissionController::class, 'listPermissions']);
    // Route::post('storePermission', [RolePermissionController::class, 'storePermission']);
    // Route::put('updatePermission', [RolePermissionController::class, 'updatePermission']);
    // Route::delete('deletePermission', [RolePermissionController::class, 'deletePermission']);
    // Route::post('assignRole', [RolePermissionController::class, 'assignRole']);
    // Route::post('assignPermission', [RolePermissionController::class, 'assignPermission']);
    // Route::post('rolesPermissionsOverview', [RolePermissionController::class, 'rolesPermissionsOverview']);

    Route::post('getInvoice', [InvoiceController::class, 'getInvoice']);
    Route::post('addUpdateInvoice', [InvoiceController::class, 'addUpdateInvoice']);
    Route::get('/orderinvoicedocs/download/{filename}', [InvoiceController::class, 'downloadInvoice']);


    # Profile details
    Route::post('updateProfile', [ProfileController::class, 'updateProfile']);
    Route::post('getProfile', [ProfileController::class, 'getProfile']);
    Route::post('updatePassWword', [ProfileController::class, 'updatePassWword']);

    # Report
    Route::post('quotationStatusReport', [QuotationSummaryController::class, 'quotationStatusReport']);
    Route::post('quotationBranchReport', [QuotationSummaryController::class, 'quotationBranchReport']);
    Route::post('quotationOwnerReport', [QuotationSummaryController::class, 'quotationOwnerReport']);
    Route::post('quotationPrincipalDealerReport', [QuotationSummaryController::class, 'quotationPrincipalDealerReport']);
});

