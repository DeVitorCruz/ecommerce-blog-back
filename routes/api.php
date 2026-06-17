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
 use App\Http\Controllers\Api\FAQController;
 use App\Http\Controllers\Api\BlogController;
 use App\Http\Controllers\Api\ContactController;
 use App\Http\Controllers\Api\Admin\OrderStatusController;
 use App\Http\Controllers\Api\UserProfileController;
 use App\Http\Controllers\Api\EmploymentController;
 use App\Http\Controllers\Api\TeamController;
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

 // ------- Blog (public) -----------------------------
 Route::get('/blogs', [BlogController::class, 'index']);
 Route::get('/blogs/{blog}', [BlogController::class, 'show']);

 // ------- FAQ (public) -----------------------------
 Route::get('/faqs', [FAQController::class, 'index']);

 // ------- Contact (public + guest) -----------------------------
 Route::post('/contacts', [ContactController::class, 'store'])
    ->middleware('auth:sanctum')->withoutMiddleware('auth:sanctum');
     
 // ------ Authencticated routes ---------------------------
 Route::middleware('auth:sanctum')->group(function () {
  
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.logout');

    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);

    // Blog (write)
    Route::post('/blogs', [BlogController::class, 'store']);
    Route::patch('/blogs/{blog}', [BlogController::class, 'update']);
    Route::delete('/blogs/{blog}', [BlogController::class, 'destroy']);

    // Seller
    Route::get('/seller/profile', [SellerController::class, 'show']);
    Route::post('/seller/onboard', [SellerController::class, 'store']);
    Route::patch('/seller/profile', [SellerController::class, 'update']);

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
		Route::patch('/sellers/{seller}/suspend', [App\Http\Controllers\Api\Admin\SellerApprovalController::class, 'suspend']);

        // Blog admin
        Route::get('/blogs', [BlogController::class, 'adminIndex']);
        
        // FAQ management
        Route::post('/faqs', [FAQController::class, 'store']);
        Route::patch('/faqs/{faq}', [FAQController::class, 'update']);
        Route::delete('/faqs/{faq}', [FAQController::class, 'destroy']);

        // Contact management
        Route::get('/contacts', [ContactController::class, 'index']);
        Route::get('/contacts/{contact}', [ContactController::class, 'show']);
        Route::patch('/contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);

		// Order management
		Route::get('/orders', [OrderStatusController::class, 'index']);
		Route::patch('/orders/{order}/status', [OrderStatusController::class, 'updateStatus']);
	});
	
    // Teams

    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::patch('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember']);
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);

    // Employment
    Route::get('/employments', [EmploymentController::class, 'index']);
    Route::post('/employments', [EmploymentController::class, 'store']);
    Route::get('/employments/{employment}', [EmploymentController::class, 'show']);
    Route::patch('/employments/{employment}/suspend', [EmploymentController::class, 'suspend']);
    Route::delete('/employments/{employment}', [EmploymentController::class, 'destroy']);

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
