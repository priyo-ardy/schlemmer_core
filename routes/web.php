<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Material\MaterialController;
use App\Http\Controllers\Process\ProcessController;
use App\Http\Controllers\Units\UnitController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {

    Route::get('/', [AuthController::class, 'index'])
        ->name('login');
    Route::post('/', [AuthController::class, 'store'])->middleware('throttle:5,1');

    Route::get('forgot-password', [AuthController::class, 'forgotPassword'])
        ->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])
        ->name('password.email')->middleware('throttle:5,1');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard/Dashboard');
    })->name('dashboard');

    // Process Management

    Route::get('/process', [ProcessController::class, 'index'])->name('process.index');
    Route::get('/process/create', [ProcessController::class, 'create'])->name('process.create');
    Route::post('/process/store', [ProcessController::class, 'store'])->name('process.store');
    Route::get('/process/{process}', [ProcessController::class, 'view'])->name('process.view');
    Route::get('/process/logs/{process}', [ProcessController::class, 'getChangeLogs'])->name('process.logs');
    Route::get('/process/logs/detail/{log_id}', [ProcessController::class, 'getChangeLogsDetails'])->name('process.logs_details');
    Route::put('/process/{process}', [ProcessController::class, 'update'])->name('process.update');
    Route::post('/process/delete', [ProcessController::class, 'delete'])->name('process.delete');
    Route::post('/process/remove', [ProcessController::class, 'remove'])->name('process.remove');

    // Customer management
    Route::get('/customer', [CustomerController::class, 'index'])->name('customer');
    Route::post('/customer', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/customer/{customer}', [CustomerController::class, 'view'])->name('customer.view');
    Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customer.update');
    Route::post('/customer/delete', [CustomerController::class, 'delete'])->name('customer.delete');
    Route::post('/customer/mass-delete', [CustomerController::class, 'massDelete'])->name('customer.mass-delete');
    Route::get('/customer/log/{logId}', [CustomerController::class, 'getLog'])->name('customer.getlog');

    // Units management
    Route::get('/units', [UnitController::class, 'index'])->name('unit.index');
    Route::post('/units', [UnitController::class, 'store'])->name('unit.store');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('unit.update');
    Route::post('/units/mass-delete', [UnitController::class, 'massDelete'])->name('unit.delete');

    // Material Management
    Route::get('/materials', [MaterialController::class, 'index'])->name('material.index');
    Route::get('/materials/create', [MaterialController::class, 'create'])->name('material.create');

    // Menu user management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-delete');

    Route::post('/logout', function (Request $request) {
        FacadesAuth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
