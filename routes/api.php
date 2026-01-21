<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\SellerProductController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.logout');

    Route::post('/seller/onboard', [SellerController::class, 'store']);

    Route::post('/products/add', [SellerProductController::class, 'store']);
});
