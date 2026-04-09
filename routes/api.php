<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\SellerProductController;
use App\Http\Controllers\Api\CategoryController;

// -------- Auth (public) --------------------

Route::post('/register', [AuthController::class, 'register'])
    ->middleware(['guest:' . config('fortify.guard')])
    ->name('api.register');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest:' . config('fortify.guard')])
    ->name('api.login');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware(['guest:' . config('fortify.guard')])
    ->name('api.forgotPassword');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware(['guest:' . config('fortify.guard')])
    ->name('api.resetPassword');
    
// ---- Categories (public) ------------------------
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// ---- Authenticated routes --------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.logout');
	
	// Seller
    Route::post('/seller/onboard', [SellerController::class, 'store']);
	
	// Products
    Route::post('/products/add', [SellerProductController::class, 'store']);
    
    // Categories - suggest (authenticated users only)
    Route::post('/categories', [CategoryController::class, 'store']);
});
