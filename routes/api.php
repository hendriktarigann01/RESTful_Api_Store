<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuestController;

Route::apiResource('products', ProductController::class);
Route::apiResource('cart', CartController::class);
Route::apiResource('admin', AdminController::class);
Route::apiResource('guest', GuestController::class);
