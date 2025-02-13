<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DataPsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesCodesController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Sales\SalesDataPsController;
use App\Http\Controllers\Api\User\UserDashboardController;
use App\Http\Controllers\Api\Sales\SalesCodesDataController;
use App\Http\Controllers\Api\Sales\SalesDashboardController;
use App\Http\Controllers\Api\User\UserCodesDataController;
use App\Http\Controllers\Api\User\UserDataPsController;

//Reigter Route
Route::prefix('admin')->group(function () {
    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']); // Get user by ID
        Route::post('/users/store', [UserController::class, 'store']); // Register a new user
        Route::post('/users/import', [UserController::class, 'import']); // Import users from Excel
        Route::put('/users/update/{id}', [UserController::class, 'update']); // Update an existing user
    });
});


Route::get('landing-page', [LandingPageController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('update', [UserController::class, 'update']);
    });

    Route::get('dashboard', [DashboardController::class, 'dashboard']);
});

//Data Route
Route::middleware(['auth:api'])->prefix('data')->group(function () {
    Route::get('/', [DataController::class, 'index']);
    Route::get('/{id}', [DataController::class, 'showDetails']);
});

// DataPs routes (Accessible by admin, sales, and user with different permissions)
Route::middleware(['auth:api'])->prefix('data-ps')->group(function () {
    // Routes accessible by all authenticated roles
    Route::get('/', [DataPsController::class, 'index']);
    Route::get('/{id}', [DataPsController::class, 'show']);

    // Routes accessible only by admin
    Route::middleware(['role:admin|sales'])->group(function () {
        Route::post('/store', [DataPsController::class, 'store']);
        Route::put('/{id}', [DataPsController::class, 'update']);
        Route::delete('/{id}', [DataPsController::class, 'destroy']);
        Route::post('/import', [DataPsController::class, 'importExcel']);
        Route::post('/set-target', [DataPsController::class, 'saveTargetGrowth']);

        // Route untuk menghapus semua data
        Route::delete('/', [DataPsController::class, 'destroyAll']);
    });

    // Routes with different access levels
    Route::middleware(['role:admin|sales|user'])->group(function () {
        // Routes for admin and sales
        Route::get('/analysis/sto', [DataPsController::class, 'analysisBySto']);
        Route::get('/analysis/month', [DataPsController::class, 'analysisByMonth']);
        Route::get('/analysis/code', [DataPsController::class, 'analysisByCode']);
        Route::get('/analysis/mitra', [DataPsController::class, 'analysisByMitra']);
        Route::get('/sto/chart', [DataPsController::class, 'stoChart']);
        Route::get('/sto/pie-chart', [DataPsController::class, 'stoPieChart']);
        Route::get('/mitra/bar-chart', [DataPsController::class, 'mitraBarChartAnalysis']);
        Route::get('/mitra/pie-chart', [DataPsController::class, 'mitraPieChartAnalysis']);
        Route::get('/day/analysis', [DataPsController::class, 'dayAnalysis']);
        Route::get('/target/tracking', [DataPsController::class, 'targetTrackingAndSalesChart']);
    });

    // Read-only routes for all authenticated users
    Route::get('/sto-list', [DataPsController::class, 'getStoList']);
    Route::get('/month-list', [DataPsController::class, 'getMonthList']);
    Route::get('/date-list', [DataPsController::class, 'getDateList']);
    Route::get('/mitra-list', [DataPsController::class, 'getMitraList']);
});

// SalesCodes routes 
Route::middleware(['auth:api'])->prefix('sales-codes')->group(function () {
    // Routes accessible by all authenticated roles
    Route::get('/', [SalesCodesController::class, 'index']);
    Route::get('/{id}', [SalesCodesController::class, 'show']);

    // Routes accessible only by admin
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/store', [SalesCodesController::class, 'store']);
        Route::put('/update/{id}', [SalesCodesController::class, 'update']);
        Route::delete('/{id}', [SalesCodesController::class, 'destroy']);
        Route::delete('/', [SalesCodesController::class, 'destroyAll']);
        Route::post('/import', [SalesCodesController::class, 'importExcel']);
    });
});

// Specific role routes can remain the same

Route::middleware('auth:api')
    ->group(function () {
        Route::prefix('sales')
            ->middleware('role:sales')
            ->group(function () {
                // Routes for sales data
                Route::get('/dashboard', [SalesDashboardController::class, 'dashboard']);
                Route::get('/sto-list', [SalesDataPsController::class, 'getStoList']);
                Route::get('/month-list', [SalesDataPsController::class, 'getMonthList']);
                Route::get('/date-list', [SalesDataPsController::class, 'getDateList']);
                Route::get('/mitra-list', [SalesDataPsController::class, 'getMitraList']);
                Route::get('/', [SalesDataPsController::class, 'index']);
                Route::get('/{id}', [SalesDataPsController::class, 'show']);
                Route::get('/analysis/sto', [SalesDataPsController::class, 'analysisBySto']);
                Route::get('/analysis/month', [SalesDataPsController::class, 'analysisByMonth']);
                Route::get('/analysis/code', [SalesDataPsController::class, 'analysisByCode']);
                Route::get('/analysis/mitra', [SalesDataPsController::class, 'analysisByMitra']);
                Route::get('/sto-chart', [SalesDataPsController::class, 'stoChart']);
                Route::get('/sto-pie-chart', [SalesDataPsController::class, 'stoPieChart']);
                Route::get('/mitra/bar-chart', [SalesDataPsController::class, 'mitraBarChartAnalysis']);
                Route::get('/mitra/pie-chart', [SalesDataPsController::class, 'mitraPieChartAnalysis']);
                Route::get('/day/analysis', [SalesDataPsController::class, 'dayAnalysis']);
                Route::post('/set-target', [SalesDataPsController::class, 'saveTargetGrowth']);
                Route::get('/target/tracking', [SalesDataPsController::class, 'targetTrackingAndSalesChart']);
                Route::post('/store', [SalesDataPsController::class, 'store']);
                Route::get('/edit/{id}', [SalesDataPsController::class, 'edit']);
                Route::put('/update/{id}', [SalesDataPsController::class, 'update']);
                Route::delete('/destroy/{id}', [SalesDataPsController::class, 'delete']);
                Route::post('/import/excel', [SalesDataPsController::class, 'importExcel']);

                // Sales codes routes
                Route::get('sales-codes/', [SalesCodesDataController::class, 'index']);
                Route::get('sales-codes/{id}', [SalesCodesDataController::class, 'show']);
            });
    });


Route::middleware('auth:api')
    ->group(function () {
        Route::prefix('user')
            ->middleware('role:user')
            ->group(function () {
                // Dashboard route
                Route::get('/dashboard', [UserDashboardController::class, 'dashboard']);

                // Data PS routes
                Route::get('/sto-list', [UserDataPsController::class, 'getStoList']);
                Route::get('/month-list', [UserDataPsController::class, 'getMonthList']);
                Route::get('/date-list', [UserDataPsController::class, 'getDateList']);
                Route::get('/mitra-list', [UserDataPsController::class, 'getMitraList']);
                Route::get('/', [UserDataPsController::class, 'index']);
                Route::get('/{id}', [UserDataPsController::class, 'show']);
                Route::get('/analysis/sto', [UserDataPsController::class, 'analysisBySto']);
                Route::get('/analysis/month', [UserDataPsController::class, 'analysisByMonth']);
                Route::get('/analysis/code', [UserDataPsController::class, 'analysisByCode']);
                Route::get('/analysis/mitra', [UserDataPsController::class, 'analysisByMitra']);
                Route::get('/sto-chart', [UserDataPsController::class, 'stoChart']);
                Route::get('/sto-pie-chart', [UserDataPsController::class, 'stoPieChart']);
                Route::get('/mitra/bar-chart', [UserDataPsController::class, 'mitraBarChartAnalysis']);
                Route::get('/mitra/pie-chart', [UserDataPsController::class, 'mitraPieChartAnalysis']);
                Route::get('/day/analysis', [UserDataPsController::class, 'dayAnalysis']);
                Route::get('/target/tracking', [UserDataPsController::class, 'targetTrackingAndSalesChart']);

                // Sales codes routes
                Route::get('sales-codes/', [UserCodesDataController::class, 'index']);
                Route::get('sales-codes/{id}', [UserCodesDataController::class, 'show']);
            });
    });
