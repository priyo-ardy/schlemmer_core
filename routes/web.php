<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Process\ProcessController;
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
    Route::put('/process/{process}', [ProcessController::class, 'update'])->name('process.update');
    Route::post('/process/bulk-delete', [ProcessController::class, 'delete'])->name('process.bulk-delete');

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
