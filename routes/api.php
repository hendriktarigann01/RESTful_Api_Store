<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AuthController;

// 🔹 AUTHENTICATION ROUTES
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// 🔹 GUEST (Tanpa Login)
Route::get('products', [GuestController::class, 'index']);

// 🔹 CUSTOMER (Harus Login)
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // 🔹 CART (Harus Login)
    Route::get('get-product', [AdminController::class, 'index']);
    Route::post('create-product', [AdminController::class, 'store']);
    Route::post('update-product/{id}', [AdminController::class, 'update']);
    Route::apiResource('cart', CartController::class);
});

// 🔹 ADMIN
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::apiResource('products', ProductController::class); // CRUD API
    Route::apiResource('admin', AdminController::class);
});
