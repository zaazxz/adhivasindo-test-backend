<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\MainController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;

Route::get('/', [MainController::class, 'index']);


// Authentication
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Products
Route::prefix('products')->group(function () {
    Route::middleware('optional.auth')->group(function () {
        Route::get('/best-sellers', [ProductController::class, 'bestSellers']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::match(['put', 'patch'], '/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);

        // Image Upload
        Route::post('/upload-image', [ProductController::class, 'uploadImage']);
    });
});

// Product Types
Route::prefix('product-types')->group(function () {
    Route::middleware('optional.auth')->group(function () {
        Route::get('/', [ProductTypeController::class, 'index']);
    });

    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::post('/', [ProductTypeController::class, 'store']);
        Route::match(['put', 'patch'], '/{id}', [ProductTypeController::class, 'update']);
        Route::delete('/{id}', [ProductTypeController::class, 'destroy']);
    });
});

// Orders
Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::match(['put', 'patch'], '/{id}/status', [OrderController::class, 'updateStatus']);
});
