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
Route::middleware(['auth:api'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // 🔹 Product (Harus Login)
    Route::get('get-product', [AdminController::class, 'index']);
    Route::get('get-product/{id}', [AdminController::class, 'show']);
    Route::post('create-product', [AdminController::class, 'store']);
    Route::post('update-product/{id}', [AdminController::class, 'update']);
    Route::delete('delete-product/{id}', [AdminController::class, 'destroy']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('get-cart', [CartController::class, 'index']);
    Route::get('get-cart/{id}', [CartController::class, 'show']);
    Route::post('create-cart', [CartController::class, 'store']);
    Route::post('update-cart/{id}', [CartController::class, 'update']);
    Route::delete('delete-cart/{id}', [CartController::class, 'destroy']);
});

// // 🔹 ADMIN
// Route::middleware(['auth:api', 'admin'])->group(function () {
//     Route::apiResource('products', ProductController::class); // CRUD API
//     Route::apiResource('admin', AdminController::class);
// });
