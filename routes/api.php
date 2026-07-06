<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\Vendor\DashboardController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

// Health-check publik — verifikasi API hidup
Route::get('/health', fn () => response()->json([
    'ok' => true,
    'app' => config('app.name'),
    'time' => now()->toIso8601String(),
]));

// Auth (dibatasi: 5 percobaan per menit)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Katalog publik
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/regions', [RegionController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/vendors/nearby', [VendorController::class, 'nearby']); // "batik di sekitarku"
Route::get('/vendors/{slug}', [VendorController::class, 'show']);

// Midtrans webhook (publik)
Route::post('/orders/notification', [OrderController::class, 'notification']);

// Perlu login (Sanctum)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);

    // Checkout & Orders
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'history']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Vendor Dashboard (perlu vendor + throttle lebih longgar)
    Route::middleware('vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/products', [DashboardController::class, 'products']);
        Route::post('/products', [DashboardController::class, 'storeProduct']);
        Route::put('/products/{product}', [DashboardController::class, 'updateProduct']);
        Route::delete('/products/{product}', [DashboardController::class, 'destroyProduct']);
        Route::get('/orders', [DashboardController::class, 'orders']);
        Route::put('/orders/{order}/status', [DashboardController::class, 'updateOrderStatus']);
        Route::get('/profile', [DashboardController::class, 'profile']);
        Route::put('/profile', [DashboardController::class, 'updateProfile']);
    });
});
