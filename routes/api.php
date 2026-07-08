<?php

use App\Http\Controllers\api\v1\Customer\CustomerApiController;
use App\Http\Controllers\api\v1\Material\MaterialApiController;
use App\Http\Controllers\api\v1\Units\UnitApiController;
use App\Http\Controllers\api\v1\UnitCategory\UnitCategoryApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth')->group(function () {
    // API Buat dropdown
    Route::get('/units_category', [UnitCategoryApiController::class, 'list']);
    Route::get('/units_category/list', [UnitCategoryApiController::class, 'list']);

    Route::get('/units', [UnitApiController::class, 'list']);
    Route::get('/units/list', [UnitApiController::class, 'list']);

    Route::get('/customers', [CustomerApiController::class, 'dropdown']);
    Route::get('/customers/list', [CustomerApiController::class, 'list']);

    Route::get('/materials', [MaterialApiController::class, 'dropdown']);
    Route::get('/materials/list', [MaterialApiController::class, 'list']); // API untuk material
});
