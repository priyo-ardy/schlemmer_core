<?php

use App\Http\Controllers\ActivityLog\ActivityLogController;
use App\Http\Controllers\api\v1\Customer\CustomerApiController;
use App\Http\Controllers\api\v1\Material\MaterialApiController;
use App\Http\Controllers\api\v1\PfmeaApiController;
use App\Http\Controllers\api\v1\ProcessTemplate\ProcessTemplateApiController;
use App\Http\Controllers\api\v1\Project\ProjectApiController;
use App\Http\Controllers\api\v1\UnitCategory\UnitCategoryApiController;
use App\Http\Controllers\api\v1\Units\UnitApiController as UnitsUnitApiController;
use App\Http\Controllers\api\v1\User\UserApiController;
use App\Http\Controllers\AppRole\RoleController;
use App\Http\Controllers\ApprovalSetup\ApprovalSetupController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Material\MaterialController;
use App\Http\Controllers\Pfmea\PfmeaController;
use App\Http\Controllers\Process\ProcessController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\RecycleBin\RecycleBinController;
use App\Http\Controllers\UnitCategory\UnitCategoryController;
use App\Http\Controllers\Units\UnitController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {

    Route::get('/', [AuthController::class, 'index'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');

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

    // Transaction
    Route::get('/pfmea', [PfmeaController::class, 'index'])->name('pfmea');
    Route::get('/pfmea/create', [PfmeaController::class, 'create'])->name('pfmea.create');
    Route::post('/pfmea/store', [PfmeaController::class, 'store'])->name('pfmea.store');
    Route::get('/pfmea/{id}/view', [PfmeaController::class, 'view'])->name('pfmea.view');
    Route::put('/pfmea/{id}', [PfmeaController::class, 'update'])->name('pfmea.update');
    Route::get('/pfmea/{id}/logs', [PfmeaController::class, 'getLogs'])->name('pfmea.logs');

    // Process Management

    Route::get('/process', [ProcessController::class, 'index'])->name('process.index');
    Route::get('/process/create', [ProcessController::class, 'create'])->name('process.create');
    Route::post('/process/store', [ProcessController::class, 'store'])->name('process.store');
    Route::get('/process/{process}', [ProcessController::class, 'view'])->name('process.view');
    Route::get('/process/logs/{process}', [ProcessController::class, 'getChangeLogs'])->name('process.logs');
    Route::get('/process/logs/detail/{log_id}', [ProcessController::class, 'getChangeLogsDetails'])->name('process.logs_details');
    Route::put('/process/{process}', [ProcessController::class, 'update'])->name('process.update');
    Route::post('/process/delete', [ProcessController::class, 'delete'])->name('process.delete');
    Route::post('/process/mass-delete', [ProcessController::class, 'massDelete'])->name('process.mass-delete');
    Route::get('/process/{logId}/logs', [ProcessController::class, 'getLog'])->name('process.getlog');

    // Customer management
    Route::get('/customer', [CustomerController::class, 'index'])->name('customer')->middleware('permission:view customers');
    Route::post('/customer', [CustomerController::class, 'store'])->name('customer.store')->middleware('permission:create customers');
    Route::get('/customer/{customer}', [CustomerController::class, 'view'])->name('customer.view')->middleware('permission:view customers');
    Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customer.update')->middleware('permission:edit customers');
    Route::post('/customer/delete', [CustomerController::class, 'delete'])->name('customer.delete')->middleware('permission:delete customers');
    Route::post('/customer/mass-delete', [CustomerController::class, 'massDelete'])->name('customer.mass-delete')->middleware('permission:mass_delete customers');
    Route::get('/customer/{logId}/logs', [CustomerController::class, 'getLog'])->name('customer.getlog');

    // Project management
    Route::get('/projects', [ProjectController::class, 'index'])->name('project.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('project.create');
    Route::get('/projects/view/{id}', [ProjectController::class, 'view'])->name('projects.view');
    Route::post('/projects', [ProjectController::class, 'store'])->name('project.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('project.update');
    Route::post('/projects/delete', [ProjectController::class, 'delete'])->name('project.delete');
    Route::post('/projects/mass-delete', [ProjectController::class, 'massDelete'])->name('project.mass-delete');
    Route::get('/projects/{project}/logs', [ProjectController::class, 'getLog'])->name('project.logs');

    // UoM Category
    Route::get('/unit_category', [UnitCategoryController::class, 'index'])->name('uom_category.index');
    Route::post('/unit_category', [UnitCategoryController::class, 'store'])->name('uom_category.store');
    Route::put('/unit_category/{category}', [UnitCategoryController::class, 'update'])->name('uom_category.update');
    Route::post('/unit_category/mass-delete', [UnitCategoryController::class, 'massDelete'])->name('uom_category.mass-delete');
    Route::get('/unit_category/{logId}/logs', [UnitCategoryController::class, 'getLog'])->name('uom_category.getlog');

    // Units management
    Route::get('/units', [UnitController::class, 'index'])->name('unit.index');
    Route::post('/units', [UnitController::class, 'store'])->name('unit.store');
    Route::put('/units/{unit}', [UnitController::class, 'update'])->name('unit.update');
    Route::post('/units/mass-delete', [UnitController::class, 'massDelete'])->name('unit.delete');
    Route::get('/units/{logId}/logs', [UnitController::class, 'getLog'])->name('unit.getlog');

    // Material Management
    Route::get('/materials', [MaterialController::class, 'index'])->name('material.index');
    Route::post('/materials', [MaterialController::class, 'store'])->name('material.store');
    Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('material.update');
    Route::post('/materials/mass-delete', [MaterialController::class, 'massDelete'])->name('material.delete');
    Route::get('/materials/{logId}/logs', [MaterialController::class, 'getLog'])->name('material.getlog');

    // Approval Management
    Route::get('/approval-setup', [ApprovalSetupController::class, 'index'])->name('approval-setup.index')->middleware('permission:view approval-setup');
    Route::get('/approval-setup/create', [ApprovalSetupController::class, 'create'])->name('approval-setup.create')->middleware('permission:create approval-setup');
    Route::post('/approval-setup', [ApprovalSetupController::class, 'store'])->name('approval-setup.index')->middleware('permission:create approval-setup');
    Route::get('/approval-setup/{id}/view', [ApprovalSetupController::class, 'view'])->name('approval-setup.view')->middleware('permission:view approval-setup');
    Route::put('/approval-setup/{id}', [ApprovalSetupController::class, 'update'])->name('approval-setup.update')->middleware('permission:edit approval-setup');
    Route::get('/approval-setup/{id}/logs', [ApprovalSetupController::class, 'getLogs'])->name('approval-setup.logs')->middleware('permission:view approval-setup');
    Route::post('/approval-setup/delete', [ApprovalSetupController::class, 'delete'])->name('approval-setup.delete')->middleware('permission:delete approval-setup');
    Route::post('/approval-setup/mass-delete', [ApprovalSetupController::class, 'massDelete'])->name('approval-setup.mass-delete')->middleware('permission:mass_delete approval-setup');

    // Menu user management
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:view users');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:create users');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:edit users');
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-delete')->middleware('permission:delete users');

    // Menu User Role
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::get('/roles/{id}/logs', [RoleController::class, 'getLogs'])->name('roles.logs');
    Route::post('/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');

    Route::post('/logout', function (Request $request) {
        FacadesAuth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    // Recycle Bin
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
});

// Route buat API
Route::prefix('api/v1')->middleware('auth')->group(function () {
    Route::get('/pfmea/', [PfmeaApiController::class, 'dataList']);

    // API Buat dropdown
    Route::get('/units_category', [UnitCategoryApiController::class, 'list']);
    Route::get('/units_category/list', [UnitCategoryApiController::class, 'list']);

    Route::get('/units', [UnitsUnitApiController::class, 'list']);
    Route::get('/units/list', [UnitsUnitApiController::class, 'list']);

    Route::get('/customers', [CustomerApiController::class, 'dropdown']);
    Route::get('/customers/list', [CustomerApiController::class, 'list']);

    Route::get('/materials', [MaterialApiController::class, 'dropdown']);
    Route::get('/materials/list', [MaterialApiController::class, 'list']);

    Route::get('projects', [ProjectApiController::class, 'dropdown']);
    Route::get('projects/{project_id}/material', [ProjectApiController::class, 'getMaterialList']);

    Route::get('/process-template', [ProcessTemplateApiController::class, 'dropdown']);

    Route::get('/users', [UserApiController::class, 'dropdown']);
});
