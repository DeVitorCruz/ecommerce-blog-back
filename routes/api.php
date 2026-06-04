<?php
 /**
  * API Routes - ecommerce-blog-back
  * 
  * Route groups:
  *  - Public Auth       : register, login, password reset
  *  - public Browse     : categories tree, product listing and search
  *  - Authenticated     : user profile, logout, seller onboarding,
  *                        product CRUD, category suggestions
  *  - Admin (role=admin): category and seller approval/rejection
  * 
  * Authentication: Laravel Sanctum (Bearer token)
  * Authorization: Spatie Laravel Permission (roles + middleware)
  */
	
 use App\Http\Controllers\AuthController;
 use App\Http\Controllers\Api\SellerController;
 use App\Http\Controllers\Api\SellerProductController;
 use App\Http\Controllers\Api\CategoryController;
 use App\Http\Controllers\Api\ProductController;
 use App\Http\Controllers\Api\CartController;
 use App\Http\Controllers\Api\OrderController;
 use App\Http\Controllers\Api\Admin\OrderStatusController;
 use Illuminate\Http\Request;
 use Illuminate\Support\Facades\Route;

 // --------- Public: Authentication ---------------------------
 Route::post('/register', [AuthController::class, 'register'])
     ->middleware(['guest:'.config('fortify.guard')])
     ->name('api.register');

 Route::post('/login', [AuthController::class, 'login'])
     ->middleware(['guest:'.config('fortify.guard')])
     ->name('api.login');

 Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
     ->middleware(['guest:'.config('fortify.guard')])
     ->name('api.forgotPassword');

 Route::post('/reset-password', [AuthController::class, 'resetPassword'])
     ->middleware(['guest:'.config('fortify.guard')])
     ->name('api.resetPassword');

 // ------ Categories (public) -----------------------------
 Route::get('/categories', [CategoryController::class, 'index']);
 Route::get('/categories/{category}', [CategoryController::class, 'show']);

 // ------ Products (public) -------------------------------
 Route::get('/products', [ProductController::class, 'index']);
 Route::get('/products/{product}', [ProductController::class, 'show']);

 // ------ Authencticated routes ---------------------------
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
    Route::patch('/products/{product}', [SellerProductController::class, 'update']);
    Route::delete('/products/{product}', [SellerProductController::class, 'destroy']);
    Route::get('/seller/products', [ProductController::class, 'sellerProducts']);

    // Categories
    Route::post('/categories', [CategoryController::class, 'store']);
    
    // --- Admin routes ------------------------
    Route::middleware('admin')->prefix('admin')->group(function () {
		
		// Category approval
		Route::get('/categories/pending', [App\Http\Controllers\Api\Admin\CategoryApprovalController::class, 'index']);
		Route::patch('/categories/{category}/approve', [App\Http\Controllers\Api\Admin\CategoryApprovalController::class, 'approve']);
		Route::patch('/categories/{category}/reject', [App\Http\Controllers\Api\Admin\CategoryApprovalController::class, 'reject']);
		
		// Seller approval
		Route::get('/sellers/pending', [App\Http\Controllers\Api\Admin\SellerApprovalController::class, 'index']);
		Route::patch('/sellers/{seller}/approve', [App\Http\Controllers\Api\Admin\SellerApprovalController::class, 'approve']);
		Route::patch('/sellers/{seller}/reject', [App\Http\Controllers\Api\Admin\SellerApprovalController::class, 'reject']);
		
		// Order management
		Route::get('/orders', [OrderStatusController::class, 'index']);
		Route::patch('/orders/{order}/status', [OrderStatusController::class, 'updateStatus']);
	});
	
	// Cart 
	Route::get('/cart', [CartController::class, 'show']);
	Route::post('/cart/items', [CartController::class, 'addItem']);
	Route::patch('/cart/items/{item}', [CartController::class, 'updateItem']);
	Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
	Route::delete('/cart', [CartController::class, 'clear']);
	Route::post('/cart/merge', [CartController::class, 'mergeGuestCart']);
	
	// Orders
	Route::get('/orders', [OrderController::class, 'index']);
	Route::post('/orders', [OrderController::class, 'store']);
	Route::get('/orders/{order}', [OrderController::class, 'show']);
	Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);	
}); 
