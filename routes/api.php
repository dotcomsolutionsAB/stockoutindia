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
use App\Http\Controllers\UserController;
use App\Http\Controllers\RazorpayController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);

Route::post('/login/{otp?}', [AuthController::class, 'login']);

Route::post('/get_otp', [AuthController::class, 'generate_otp']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

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

    Route::prefix('product')->group(function () {
        Route::post('/', [ProductController::class, 'createProduct']); // Create a new product
        Route::post('/get_products/{id?}', [ProductController::class, 'fetchProducts']); // Retrieve product (all or specific)
        Route::post('/{id}', [ProductController::class, 'updateProduct']); // Update a specific product
        Route::post('/images/{id}', [ProductController::class, 'uploadProductImages']); // Upload image for a specific product
        Route::delete('/{id}', [ProductController::class, 'deleteProduct']); // Delete a specific product
        //Route::delete('/images/{id}', [ProductController::class, 'deleteProductImages']); // Delete specific product image

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

    Route::get('/countries', [MasterController::class, 'fetchAllCountries']);
    Route::get('/states', [MasterController::class, 'fetchAllStates']);
    Route::get('/cities/{id?}', [MasterController::class, 'fetchAllCities']);

    Route::prefix('wishlist')->group(function () {
        Route::post('/add', [WishlistController::class, 'addProduct']); // Create Products
        Route::post('/fetch', [WishlistController::class, 'fetchWishlist']); // Retrieve products
        Route::delete('/{id}', [WishlistController::class, 'deleteProduct']); // Update a specific user
    });

    Route::post('/make_payment', [RazorpayController::class, 'processPayment']);
    Route::post('/fetch_payment', [RazorpayController::class, 'fetchPayments']);

    Route::post('/store_payment', [RazorpayController::class, 'storePayment']);
});

    Route::get('/get_products/{id?}', [ProductController::class, 'fetchOnlyProducts']); // Retrieve product (all or specific)

