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

            // Create the wishlist item record.
            $wishlistItem = WishlistModel::create([
                'user_id'    => $targetUserId,
                'product_id' => $validatedData['product_id'],
                'variant_id' => $validatedData['variant_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist successfully!',
                'data'    => $wishlistItem,
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

            return response()->json([
                'success' => true,
                'message' => 'Wishlist fetched successfully!',
                'data'    => $wishlistItems,
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
                ], 404);
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
