<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Multitenancy\Http\Middleware\InitializeTenancyByPath;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MigrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes within the tenant context
Route::prefix('/{tenantSlug}')
    ->middleware(['tenant.init', 'tenant'])
    ->group(function () {
        Route::post('/login', [App\Http\Controllers\LoginController::class, 'login'])
        ->name('tenant.login');

        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
            ->name('tenant.dashboard');
        
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])
            ->name('tenant.profile');
    });
        Route::post('/check', [App\Http\Controllers\LoginController::class, 'check']);

        Route::post('/tenants/migrate', [MigrationController::class, 'runMigrations']);
        Route::post('/tenant/create', [MigrationController::class, 'createTenant']);
