<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReviewModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Auth;

class ReviewController extends Controller
{
    // create
    public function createReview(Request $request)
    {
        try {
            // Ensure user is logged in
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, no user is logged in now!',
                ], 401);
            }

            // Validate request
            $validatedData = $request->validate([
                'product' => 'nullable|numeric|exists:t_products,id',
                'rating' => 'required|numeric|min:1|max:5',
                'review' => 'required|string',
            ]);

            // Create Review
            $review = new ReviewModel();
            $review->user = Auth::id(); // Logged-in user's ID
            $review->product = $validatedData['product'];
            $review->rating = $validatedData['rating'];
            $review->review = $validatedData['review'];
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review added successfully!',
                'data' => $review->makeHidden(['id', 'created_at', 'updated_at'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed!',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // view
    public function getReviews($id = null)
    {
        try {
            // Initialize query with the user relationship
                $query = ReviewModel::with(['userDetails' => function ($query) {
                    $query->select('id', 'name'); // Fetch only id and name
                },
                'productDetails' => function ($query) {
                    $query->select('id', 'product_name'); // Fetch only product id and name
                }
            ]);

            // Apply ID filter if provided
            if ($id) {
                $query->where('id', $id);
            }

            // Fetch the data
            $reviews = $query->get();

            // Check if no reviews exist
            if ($reviews->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reviews found!',
                ], 404);
            }

            // Transform response to replace user_id with user_name
            $reviews->transform(function ($review) {
                return [
                    'id' => $review->id,
                    'product' => $review->productDetails->product_name ?? 'Unknown', // Replace product_id with product name
                    'user' => $review->userDetails->name ?? 'Unknown',
                    'rating' => $review->rating,
                    'review' => $review->review,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Reviews fetched successfully!',
                'data' => $reviews,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // update
    public function updateReview(Request $request, $id)
    {
        try {
            // Ensure user is logged in
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, no user is logged in now!',
                ], 401);
            }

            // Find the review
            $review = ReviewModel::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found!',
                ], 404);
            }

            // Check if the logged-in user is the owner of the review
            if ($review->user !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this review!',
                ], 403);
            }

            // Validate request
            $validatedData = $request->validate([
                'product' => 'nullable|numeric|exists:t_products,id',
                'rating' => 'nullable|numeric|min:1|max:5',
                'review' => 'nullable|string',
            ]);

            // Update review fields if provided
            if ($request->has('product')) {
                $review->product = $validatedData['product'];
            }
            if ($request->has('rating')) {
                $review->rating = $validatedData['rating'];
            }
            if ($request->has('review')) {
                $review->review = $validatedData['review'];
            }

            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully!',
                'data' => $review->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed!',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
