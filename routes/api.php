<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubIndustryController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RazorpayController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/privacy-policy', [UserController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/terms-and-conditions', [UserController::class, 'termsConditions'])->name('terms.conditions');
Route::get('/refund-policy', [UserController::class, 'refundPolicy'])->name('refund.policy');
Route::get('/faqs', [UserController::class, 'getFaqsJson']);

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/get_otp', [AuthController::class, 'generate_otp']);
Route::post('/forget_password', [AuthController::class, 'forgotPassword']);
Route::post('/gst_details', [UserController::class, 'fetchGstDetails']);
Route::get('/banners', [UserController::class, 'fetchBanners']);
Route::post('/upload_banners', [UserController::class, 'uploadBanner']);

// Add a route in web.php
Route::get('import_users', [ImportController::class, 'importUsers']);
Route::get('import_products', [ImportController::class, 'importProducts']);

Route::prefix('industry')->group(function () {
    Route::post('/', [IndustryController::class, 'createIndustry']); // Create a new industry record
    Route::get('/{id?}', [IndustryController::class, 'getIndustries']); // Retrieve industry record (all or specific)
    Route::post('/{id}', [IndustryController::class, 'updateIndustry']); // Update a specific industry record
    Route::delete('/{id}', [IndustryController::class, 'deleteIndustry']); // delete a specific industry record
});

Route::prefix('sub_industry')->group(function () {
    Route::post('/', [SubIndustryController::class, 'createSubIndustry']); // Create a new review record
    Route::get('/{id?}', [SubIndustryController::class, 'getSubIndustries']); // Retrieve review record (all or specific)
    Route::post('/{id}', [SubIndustryController::class, 'updateSubIndustry']); // Update a specific review record
    Route::delete('/{id}', [SubIndustryController::class, 'deleteSubIndustry']); // Delete a specific review record
});

Route::post('/get_products/{id?}', [ProductController::class, 'fetchOnlyProducts']); // Retrieve product (all or specific)

Route::get('/countries', [MasterController::class, 'fetchAllCountries']);
Route::get('/states', [MasterController::class, 'fetchAllStates']);
Route::get('/cities/{id?}', [MasterController::class, 'fetchAllCities']);

Route::prefix('product')->group(function () {
    Route::get('get_units', [ProductController::class, 'getUnits']);
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/reset_password', [AuthController::class, 'resetPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('product')->group(function () {
        Route::post('/', [ProductController::class, 'createProduct']); // Create a new product
        Route::post('/get_products/{id?}', [ProductController::class, 'fetchProducts']); // Retrieve product (all or specific)
        Route::post('/update/{id}', [ProductController::class, 'updateProduct']); // Update a specific product
        Route::post('/images/{id}', [ProductController::class, 'uploadProductImages']); // Upload image for a specific product
        Route::delete('/{id}', [ProductController::class, 'deleteProduct']); // Delete a specific product
        //Route::delete('/images/{id}', [ProductController::class, 'deleteProductImages']); // Delete specific product image

        Route::post('/update_status', [ProductController::class, 'updateProductStatus']); // Update product status

        Route::get('/migration', [ProductController::class, 'importProductImagesFromCSV']); // Import migration files
    });

    Route::prefix('review')->group(function () {
        Route::post('/', [ReviewController::class, 'createReview']); // Create a new review
        Route::get('/{id?}', [ReviewController::class, 'getReviews']); // Retrieve review (all or specific)
        Route::post('/{id}', [ReviewController::class, 'updateReview']); // Update a specific review
        Route::delete('/{id}', [ReviewController::class, 'deleteReview']); // Delete a specific review
    });

    Route::prefix('user')->group(function () {
        Route::post('/', [UserController::class, 'register']); // Create a new user
        Route::get('/{id?}', [UserController::class, 'viewUsers']); // Retrieve user (all or specific)
        Route::post('/{id}', [UserController::class, 'updateUser']); // Update a speciic user
        Route::delete('/{id}', [UserController::class, 'deleteUser']); // Update a specific user
    });

    

    Route::prefix('wishlist')->group(function () {
        Route::post('/add', [WishlistController::class, 'addProduct']); // Create Products
        Route::post('/fetch', [WishlistController::class, 'fetchWishlist']); // Retrieve products
        Route::delete('/{id}', [WishlistController::class, 'deleteProduct']); // Update a specific user
    });

    Route::post('/make_payment', [RazorpayController::class, 'processPayment']);
    Route::post('/fetch_payment', [RazorpayController::class, 'fetchPayments']);

    Route::post('/store_payment', [RazorpayController::class, 'storePayment']);

    Route::middleware(['auth:sanctum', AllowAdminOrUser::class . ':user'])->group(function () {

        Route::prefix('admin')->group(function () {
            Route::post('/products', [ProductController::class, 'admin_fetchProducts']);
            Route::post('/users_with_products', [UserController::class, 'usersWithProducts']);
            Route::post('/user_orders', [UserController::class, 'userOrders']);
            Route::post('/user_toggle_status', [UserController::class, 'toggleUserStatus']);
            Route::post('/product_toggle_status', [UserController::class, 'toggleUserStatus']);
        });
    });
});


