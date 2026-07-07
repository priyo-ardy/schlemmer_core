<?php

use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Material\MaterialController;
use App\Http\Controllers\Units\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // API Buat dropdown
    Route::get('/units', [UnitController::class, 'searchUnitDropDown']);
    Route::get('/materials', [MaterialController::class, 'searchMaterialDropDown']); // API untuk material
    Route::get('/materials/list', [MaterialController::class, 'listMaterial']); // API untuk material
    Route::get('/customers', [CustomerController::class, 'searchCustomerDropDown']); // API untuk customer
});
