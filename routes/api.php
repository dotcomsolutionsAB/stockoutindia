<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubIndustryController;
use App\Http\Controllers\UsersController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [UsersController::class, 'register']);

Route::post('/login/{otp?}', [AuthController::class, 'login']);

Route::post('/get_otp', [AuthController::class, 'generate_otp']);

Route::prefix('industry')->group(function () {
    Route::post('/', [IndustryController::class, 'createIndustry']); // Create a new industry record
    Route::get('/{id?}', [IndustryController::class, 'getIndustries']); // Retrieve industry record (all or specific)
    Route::post('/{id}', [IndustryController::class, 'updateIndustry']); // Update a specific industry record
    Route::delete('/{id}', [IndustryController::class, 'deleteIndustry']); // delete a specific industry record
});

Route::prefix('sub_industry')->group(function () {
    Route::post('/', [ReviewController::class, 'createSubIndustry']); // Create a new review record
    Route::get('/{id?}', [ReviewController::class, 'getSubIndustries']); // Retrieve review record (all or specific)
    Route::post('/{id}', [ReviewController::class, 'updateSubIndustry']); // Update a specific review record
    Route::delete('/{id}', [ReviewController::class, 'deleteSubIndustry']); // Delete a specific review record
});

Route::prefix('product')->group(function () {
    Route::post('/', [ProductController::class, 'createProduct']); // Create a new product
    Route::get('/{id?}', [ProductController::class, 'fetchProducts']); // Retrieve product (all or specific)
    Route::post('/{id}', [ProductController::class, 'updateProduct']); // Update a specific product
    Route::post('/images/{id}', [ProductController::class, 'updateProductImages']); // Upload image for a specific product
    Route::delete('/{id}', [ProductController::class, 'deleteProduct']); // Delete a specific product
});

Route::prefix('review')->group(function () {
    Route::post('/', [ReviewController::class, 'createReview']); // Create a new review
    Route::get('/{id?}', [ReviewController::class, 'getReviews']); // Retrieve review (all or specific)
    Route::post('/{id}', [ReviewController::class, 'updateReview']); // Update a specific review
});
