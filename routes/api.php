<?php

use App\Http\Controllers\api\v1\Customer\CustomerApiController;
use App\Http\Controllers\api\v1\Units\UnitApiController;
use App\Http\Controllers\api\v1\UnitCategory\UnitCategoryApiController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Material\MaterialController;
use App\Http\Controllers\Units\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // API Buat dropdown
    Route::get('/units_category', [UnitCategoryApiController::class, 'list']);
    Route::get('/units', [UnitApiController::class, 'list']);
    Route::get('/customers', [CustomerApiController::class, 'list']);
    Route::get('/materials', [MaterialController::class, 'searchMaterialDropDown']); // API untuk material
    Route::get('/materials/list', [MaterialController::class, 'listMaterial']); // API untuk material
    Route::get('/customers', [CustomerController::class, 'searchCustomerDropDown']); // API untuk customer
});
