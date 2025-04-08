<?php

namespace App\Http\Controllers;
use App\Models\WishlistModel;
use Illuminate\Http\Request;
use Auth;

class WishlistController extends Controller
{
    //
    /**
     * Add a product to a user's wishlist.
     */
    public function addProduct(Request $request)
    {
        try {
            $user = Auth::user();

            // For admin, require a user_id from the request; for normal users, use authenticated user's id.
            if ($user->role === 'admin') {
                $validatedData = $request->validate([
                    'user_id'    => 'required|integer|exists:users,id',
                    'product_id' => 'required|integer|exists:t_products,id',
                ]);
                $targetUserId = $validatedData['user_id'];
            } else {
                $validatedData = $request->validate([
                    'product_id' => 'required|integer|exists:t_products,id',
                ]);
                $targetUserId = $user->id;
            }

            // Check if the product is already in the wishlist for this user
            if (WishlistModel::where('user_id', $targetUserId)
                    ->where('product_id', $validatedData['product_id'])
                    ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already exists in wishlist.'
                    ], 200); // 409 Conflict
            }

            // Create the wishlist item record.
            $wishlistItem = WishlistModel::create([
                'user_id'    => $targetUserId,
                'product_id' => $validatedData['product_id'],
                'variant_id' => $validatedData['variant_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist successfully!',
                'data'    => $wishlistItem->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);
        } catch (Exception $e) {
            \Log::error("Error adding product to wishlist: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to wishlist.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch the wishlist for a user.
     */
    public function fetchWishlist(Request $request)
    {
        try {
            $user = Auth::user();

            // For admin, require the target user_id; otherwise, use authenticated user's id.
            if ($user->role === 'admin') {
                $validatedData = $request->validate([
                    'user_id' => 'required|integer|exists:users,id',
                ]);
                $targetUserId = $validatedData['user_id'];
            } else {
                $targetUserId = $user->id;
            }

            $wishlistItems = WishlistModel::with(['product'])
                ->where('user_id', $targetUserId)
                ->get();

            // Transform each wishlist item
            $wishlistItems->transform(function ($item) {
                if ($item->product) {
                    // Step 1: Get the product's image field (a comma-separated list of upload IDs)
                    $uploadIds = $item->product->image ? explode(',', $item->product->image) : [];
                    
                    // Step 2: Query UploadModel to get file_url for each upload id.
                    $uploads = \App\Models\UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

                    // Step 3: Map each upload id to its full URL using the url() helper.
                    $item->product->image = array_map(function ($uid) use ($uploads) {
                        return isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null;
                    }, $uploadIds);
                }
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Wishlist fetched successfully!',
                'data'    => $wishlistItems->makeHidden(['id', 'created_at', 'updated_at']),
            ], 200);
        } catch (Exception $e) {
            \Log::error("Error fetching wishlist: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wishlist.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product from a user's wishlist.
     * @param int $wishlistItemId The ID of the wishlist item to delete.
     */
    public function deleteProduct(Request $request, $wishlistItemId)
    {
        try {
            $user = Auth::user();

            // For admin, require a target user_id to validate deletion; otherwise, use authenticated user's id.
            if ($user->role === 'admin') {
                $validatedData = $request->validate([
                    'user_id' => 'required|integer|exists:users,id',
                ]);
                $targetUserId = $validatedData['user_id'];
            } else {
                $targetUserId = $user->id;
            }

            // Find the wishlist item ensuring it belongs to the target user.
            $wishlistItem = WishlistModel::where('product_id', $wishlistItemId)
                ->where('user_id', $targetUserId)
                ->first();

            if (!$wishlistItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wishlist item not found or you are not authorized to delete this item.',
                ], 200);
            }

            $wishlistItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist item deleted successfully!',
            ], 200);
        } catch (Exception $e) {
            \Log::error("Error deleting wishlist item: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete wishlist item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
