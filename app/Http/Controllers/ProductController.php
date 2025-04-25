<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
use App\Models\UploadModel;
use App\Models\WishlistModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Auth;
use DB;

class ProductController extends Controller
{
    //create
    public function createProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_name' => 'required|string|max:255',
                'original_price' => 'required|numeric',
                'selling_price' => 'required|numeric',
                'offer_quantity' => 'required|integer',
                'minimum_quantity' => 'required|integer',
                'unit' => 'required|string|max:255',
                'industry' => 'required|integer|exists:t_industries,id',
                'sub_industry' => 'required|integer|exists:t_sub_industries,id',
                'city' => 'nullable|string|max:255',
                'state_id' => 'nullable|integer|exists:t_states,id',
                'status' => 'sometimes|in:active,in-active',
                'description' => 'nullable|string',
                'dimensions' => 'nullable|string|max:256',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $product = ProductModel::create([
                'user_id' => Auth::id(), // or pass in from request if needed
                'product_name' => $request->product_name,
                'original_price' => $request->original_price,
                'selling_price' => $request->selling_price,
                'offer_quantity' => $request->offer_quantity,
                'minimum_quantity' => $request->minimum_quantity,
                'unit' => $request->unit,
                'industry' => $request->industry,
                'sub_industry' => $request->sub_industry,
                'city' => $request->city,
                'state_id' => $request->state_id,
                'status' => $request->status ?? 'in-active',
                'description' => $request->description,
                'dimensions' => $request->dimensions,
                'is_delete' => '0',
                // 'image' => will be empty or null initially; handled in separate method
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully!',
                'data' => $product->makeHidden(['created_at', 'updated_at']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // view
    // for logged-in user
    public function fetchProducts(Request $request, $id = null)
    {
        try {
            if ($id) {
                // Fetch a single product with related user, industry, and sub-industry
                $product = ProductModel::with([
                    'user:id,name,phone,city',
                    'industryDetails:id,name',
                    'subIndustryDetails:id,name'
                ])->where('is_delete', 0)
                ->find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found!',
                    ], 200);
                }

                // Parse images
                $uploadIds = $product->image ? explode(',', $product->image) : [];
                $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

                $product->image = array_map(fn($uid) => isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null, $uploadIds);

                // Check if product is in the wishlist for the current user
                $isWishlist = WishlistModel::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
                // Format response correctly
                $responseData = [
                    'user' => [
                        'name' => optional($product->user)->name,
                        'phone' => optional($product->user)->phone,
                        'city' => optional($product->user)->city
                    ],
                    'industry' => optional($product->industryDetails)->name,
                    'sub_industry' => optional($product->subIndustryDetails)->name,
                    'is_wishlist' => $isWishlist,
                ] + $product->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Product details fetched successfully!',
                    'data' => collect($responseData)->except(['id', 'user_id', 'industry', 'sub_industry', 'created_at', 'updated_at']),
                ], 200);
            }

            // Fetch input filters
            $search = $request->input('search');
            $industryIds = $request->input('industry') ? explode(',', $request->input('industry')) : [];
            $subIndustryIds = $request->input('sub_industry') ? explode(',', $request->input('sub_industry')) : [];
            $userIds = $request->input('user_id') ? explode(',', $request->input('user_id')) : [];
            $cities = $request->input('city') ? explode(',', $request->input('city')) : [];
            $stateIds = $request->input('state_id') ? explode(',', $request->input('state_id')) : [];

            $limit = $request->input('limit', 10); // Dynamic limit (default 10)
            $offset = $request->input('offset', 0); // Default offset 0

            // Query products with relationships
            $query = ProductModel::with([
                'user:id,name,phone,city',
                'industryDetails:id,name',
                'subIndustryDetails:id,name'
            ])->where('is_delete', 0);

            // 🔹 **Fix: Search in product_name, user->name, and user->city**
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
                });
            }

            // 🔹 **Fix: Apply City Filtering for Users & Products**
            if (!empty($cities)) {
                $query->where(function ($q) use ($cities) {
                    $q->whereIn('city', $cities)
                    ->orWhereHas('user', function ($q) use ($cities) {
                        $q->whereIn('city', $cities);
                    });
                });
            }

            // Apply Filters if Provided
            if (!empty($industryIds)) {
                $query->whereIn('industry', $industryIds);
            }
            if (!empty($subIndustryIds)) {
                $query->whereIn('sub_industry', $subIndustryIds);
            }
            if (!empty($userIds)) {
                $query->whereIn('user_id', $userIds);
            }
            if (!empty($stateIds)) {
                $query->whereIn('state_id', $stateIds);
            }

            // Apply pagination
            $totalRecords = $query->count();
            $products = $query->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

            // Handle Empty Results
            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products found!',
                    'data' => [],
                    'total_record' => 0,
                ], 200);
            }

            // Get all image IDs in one go
            $allImageIds = collect($products)->flatMap(fn($p) => explode(',', $p->image ?? ''))->unique()->filter();
            $uploads = UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

            // Pre-fetch wishlist product IDs for the authenticated user
            $wishlistProductIds = WishlistModel::where('user_id', Auth::id())
            ->pluck('product_id')
            ->toArray();

            // 🔹 **Fix: Transform Each Product Correctly**
            $products->transform(function ($prod) use ($uploads, $wishlistProductIds) {
                $uploadIds = $prod->image ? explode(',', $prod->image) : [];
                $prod->image = array_map(fn($uid) => isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null, $uploadIds);

                return collect([
                    'user' => [
                        'name' => optional($prod->user)->name,
                        'phone' => optional($prod->user)->phone,
                        'city' => optional($prod->user)->city
                    ],
                    'industry' => optional($prod->industryDetails)->name,
                    'sub_industry' => optional($prod->subIndustryDetails)->name,
                    // Add is_wishlist: true if product exists in user's wishlist, else false.
                    'is_wishlist' => in_array($prod->id, $wishlistProductIds),
                ] + $prod->toArray())->except(['user_id', 'industry', 'sub_industry', 'created_at', 'updated_at']);
            });

            // Return Final JSON Response
            return response()->json([
                'success' => true,
                'message' => 'All products fetched successfully!',
                'data' => $products,
                'total_record' => $totalRecords,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // for guest-user
    public function fetchOnlyProducts(Request $request, $id = null)
    {
        try {
            if ($id) {
                // Fetch a single product
                $product = ProductModel::with([
                    'user:id,name,city', // Removed phone number
                    'industryDetails:id,name',
                    'subIndustryDetails:id,name'
                ])
                ->where('is_delete', 0)
                ->find($id);

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found!',
                    ], 404);
                }

                // Parse images
                $uploadIds = $product->image ? explode(',', $product->image) : [];
                $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

                $product->image = array_map(fn($uid) => isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null, $uploadIds);

                // Check if product is in the wishlist for the current user
                $isWishlist = WishlistModel::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();

                // Prepare response, removing phone number & unnecessary fields
                $responseData = [
                    'user' => [
                        'name' => optional($product->user)->name,
                        'city' => optional($product->user)->city
                    ],
                    'industry' => optional($product->industryDetails)->name,
                    'sub_industry' => optional($product->subIndustryDetails)->name,
                ] + $product->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Product details fetched successfully!',
                    'data' => collect($responseData)->except(['id', 'user_id', 'industry', 'sub_industry', 'created_at', 'updated_at']),
                ], 200);
            }

            // 🔹 **Filtering Section**
            $search = $request->input('search');
            $industryIds = $request->input('industry') ? explode(',', $request->input('industry')) : [];
            $subIndustryIds = $request->input('sub_industry') ? explode(',', $request->input('sub_industry')) : [];
            $userIds = $request->input('user_id') ? explode(',', $request->input('user_id')) : [];
            $cities = $request->input('city') ? explode(',', $request->input('city')) : [];
            $stateIds = $request->input('state_id') ? explode(',', $request->input('state_id')) : [];

            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            // 🔹 **Query Products with Relationships**
            $query = ProductModel::with([
                'user:id,name,city', // Removed phone number
                'industryDetails:id,name',
                'subIndustryDetails:id,name'
            ])->where('is_delete', 0);

            // 🔹 **Search in Product Name & User's City**
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
                });
            }

            // 🔹 **City Filtering**
            if (!empty($cities)) {
                $query->where(function ($q) use ($cities) {
                    $q->whereIn('city', $cities)
                    ->orWhereHas('user', function ($q) use ($cities) {
                        $q->whereIn('city', $cities);
                    });
                });
            }

            // Apply Filters
            if (!empty($industryIds)) {
                $query->whereIn('industry', $industryIds);
            }
            if (!empty($subIndustryIds)) {
                $query->whereIn('sub_industry', $subIndustryIds);
            }
            if (!empty($userIds)) {
                $query->whereIn('user_id', $userIds);
            }
            if (!empty($stateIds)) {
                $query->whereIn('state_id', $stateIds);
            }

            // Apply Pagination
            $totalRecords = $query->count();
            $products = $query->offset($offset)->limit($limit)->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products found!',
                    'data' => [],
                    'total_record' => 0,
                ], 200);
            }

            // 🔹 **Image Processing**
            $allImageIds = collect($products)->flatMap(fn($p) => explode(',', $p->image ?? ''))->unique()->filter();
            $uploads = UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

            // 🔹 **Transform Products**
            $products->transform(function ($prod) use ($uploads) {
                $uploadIds = $prod->image ? explode(',', $prod->image) : [];
                $prod->image = array_map(fn($uid) => isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null, $uploadIds);

                return collect([
                    'user' => [
                        'name' => optional($prod->user)->name,
                        'city' => optional($prod->user)->city
                    ],
                    'industry' => optional($prod->industryDetails)->name,
                    'sub_industry' => optional($prod->subIndustryDetails)->name,
                ] + $prod->toArray())->except(['user_id', 'industry', 'sub_industry', 'created_at', 'updated_at']);
            });

            return response()->json([
                'success' => true,
                'message' => 'All products fetched successfully!',
                'data' => $products,
                'total_record' => $totalRecords,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // update
    public function updateProduct(Request $request, $id)
    {
        try {
            $product = ProductModel::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 200);
            }

            $validator = Validator::make($request->all(), [
                'product_name' => 'sometimes|string|max:255',
                'original_price' => 'sometimes|numeric',
                'selling_price' => 'sometimes|numeric',
                'offer_quantity' => 'sometimes|integer',
                'minimum_quantity' => 'sometimes|integer',
                'unit' => 'sometimes|string|max:255', // Updated max length from 50 to 255 to match DB
                'industry' => 'sometimes|integer|exists:t_industries,id',
                'sub_industry' => 'sometimes|integer|exists:t_sub_industries,id',
                'city' => 'sometimes|nullable|string|max:255', // Added city field
                'state_id' => 'sometimes|nullable|integer|exists:t_states,id', // Added state_id validation
                'status' => 'sometimes|in:active,in-active,sold',
                'description' => 'sometimes|nullable|string',
                'dimensions' => 'sometimes|nullable|string|max:256', // Added dimensions field
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            // Column-wise update
            // $product->update($request->only([
            //     'product_name', 'original_price', 'selling_price', 'offer_quantity',
            //     'minimum_quantity', 'unit', 'industry', 'sub_industry', 'status', 'description'
            // ]));

            $product->update([
                'product_name' => $request->product_name ?? $product->product_name,
                'original_price' => $request->original_price ?? $product->original_price,
                'selling_price' => $request->selling_price ?? $product->selling_price,
                'offer_quantity' => $request->offer_quantity ?? $product->offer_quantity,
                'minimum_quantity' => $request->minimum_quantity ?? $product->minimum_quantity,
                'unit' => $request->unit ?? $product->unit,
                'industry' => $request->industry ?? $product->industry,
                'sub_industry' => $request->sub_industry ?? $product->sub_industry,
                'city' => $request->city ?? $product->city,
                'state_id' => $request->state_id ?? $product->state_id,
                'status' => $request->status ?? $product->status, // Defaulting to existing status if not provided
                'description' => $request->description ?? $product->description,
                'dimensions' => $request->dimensions ?? $product->dimensions,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // upload image
    //only new image will create and replace the old one
    public function uploadProductImages(Request $request, $id)
    {
        try {
            $product = ProductModel::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 200);
            }

            // Validate new files
            $validator = Validator::make($request->all(), [
                'files.*' => 'required|mimes:jpg,jpeg,png,heif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            // Delete old images from DB + server
            $oldImageIds = $product->image ? explode(',', $product->image) : [];

            foreach ($oldImageIds as $imgId) {
                $upload = UploadModel::find($imgId);
                if ($upload) {
                    // Extract only the relative file path
                    // $filePath = str_replace(asset('storage/'), '', $upload->file_url);
                    $filePath = str_replace('storage/', '', $upload->file_url); // Fix: Remove storage/ prefix

                    // Delete from server
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                    // Delete from DB
                    $upload->delete();
                }
            }

            // Get old image IDs
            $oldImageIds = $product->image ? explode(',', $product->image) : [];

            foreach ($oldImageIds as $imgId) {
                $upload = UploadModel::find($imgId);
                if ($upload) {
                    // Extract only the relative file path
                    $filePath = str_replace(asset('storage/'), '', $upload->file_url);

                    // Delete from server
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                    // Delete from DB
                    $upload->delete();
                }
            }

            $uploadIds = [];
            // Handle new files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    
                    $originalName = $file->getClientOriginalName(); // e.g. "photo.jpg"

                    // This will actually save the file to storage/app/public/uploads/products/product_images
                    $path = $file->storeAs('uploads/products/product_images', $originalName, 'public');

                    // Generate the correct relative URL, e.g. "storage/uploads/products/product_images/filename.jpg"
                    $storedPath = Storage::url($path);

                    $extension = $file->extension();                // e.g. "jpg"
                    $size = $file->getSize();                       // e.g. 123456 (bytes)

                    $upload = UploadModel::create([
                        'file_name' => $originalName,
                        'file_ext' => $extension,
                        // 'file_url' => asset("storage/$path"),
                        'file_url' => $storedPath ,
                        'file_size' => $size,
                    ]);
                    $uploadIds[] = $upload->id;
                }
            }

            // Update product image column with new comma separated IDs
            $product->image = implode(',', $uploadIds);
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product images updated successfully!',
                'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
                // 'data_product' => $product->makeHidden(['id', 'created_at', 'updated_at']),
                // 'data_upload' => $upload->makeHidden(['id', 'created_at', 'updated_at'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // append the new images
    // public function uploadProductImages(Request $request, $id)
    // {
    //     try {
    //         $product = ProductModel::find($id);
    //         if (!$product) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Product not found!',
    //             ], 404);
    //         }

    //         // Validate new files
    //         $validator = Validator::make($request->all(), [
    //             'files.*' => 'required|mimes:jpg,jpeg,png,heif|max:2048',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $validator->errors()->first(),
    //             ], 422);
    //         }

    //         // Delete old images from DB + server
    //         // $oldImageIds = $product->image ? explode(',', $product->image) : [];

    //         // foreach ($oldImageIds as $imgId) {
    //         //     $upload = UploadModel::find($imgId);
    //         //     if ($upload) {
    //         //         // Extract only the relative file path
    //         //         $filePath = str_replace(asset('storage/'), '', $upload->file_url);

    //         //         // Delete from server
    //         //         if (Storage::disk('public')->exists($filePath)) {
    //         //             Storage::disk('public')->delete($filePath);
    //         //         }
    //         //         // Delete from DB
    //         //         $upload->delete();
    //         //     }
    //         // }

    //         // Get old image IDs
    //         $oldImageIds = $product->image ? explode(',', $product->image) : [];

    //         $uploadIds = [];
    //         // Handle new files
    //         if ($request->hasFile('files')) {
    //             foreach ($request->file('files') as $file) {
    //                 // 1. Store file in "uploads/products" folder on the "public" disk
    //                 $path = $file->store('uploads/products/product_images', 'public');

    //                 // 2. Extract file details
    //                 $originalName = $file->getClientOriginalName(); // e.g. "photo.jpg"
    //                 $extension = $file->extension();                // e.g. "jpg"
    //                 $size = $file->getSize();                       // e.g. 123456 (bytes)

    //                 $upload = UploadModel::create([
    //                     'file_name' => $originalName,
    //                     'file_ext' => $extension,
    //                     'file_url' => asset("storage/$path"),
    //                     'file_size' => $size,
    //                 ]);
    //                 $uploadIds[] = $upload->id;
    //             }
    //         }

    //         // Merge old and new image IDs
    //         $product->image = implode(',', array_merge($oldImageIds, $uploadIds));
    //         $product->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Product images updated successfully!',
    //             'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
    //             // 'data_product' => $product->makeHidden(['id', 'created_at', 'updated_at']),
    //             // 'data_upload' => $upload->makeHidden(['id', 'created_at', 'updated_at'])
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // delete
    public function deleteProduct($id)
    {
        try {
            $product = ProductModel::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 200);
            }

            // Delete existing images
            $oldImageIds = $product->image ? explode(',', $product->image) : [];

            foreach ($oldImageIds as $imgId) {
                $upload = UploadModel::find($imgId);
                if ($upload) {
                    // Convert DB URL to actual storage path
                    $filePath = str_replace(asset('storage/'), '', $upload->file_url);
                    $filePath = 'uploads/products/product_images/' . basename($filePath); // Ensure correct path

                    // Debugging: Log file path before deletion
                    \Log::info("Deleting File: " . $filePath);

                    // Delete from server
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                        \Log::info("Deleted File: " . $filePath);
                    } else {
                        \Log::error("File not found for deletion: " . $filePath);
                    }

                    $upload->delete();
                }
            }

            // Finally, delete product
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function tempdeleteProduct($id)
    {
        try {
            $product = ProductModel::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 200);
            }

            // Mark product as deleted (custom soft delete)
            $product->is_delete = '1';
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product temporarily deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // delete product image
    // public function deleteProductImages(Request $request, $id)
    // {
    //     try {
    //         // Find the product
    //         $product = ProductModel::find($id);
    //         if (!$product) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Product not found!',
    //             ], 404);
    //         }

    //         // Validate input
    //         $validator = Validator::make($request->all(), [
    //             'image_ids' => 'required|array',
    //             'image_ids.*' => 'integer|exists:t_uploads,id',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $validator->errors()->first(),
    //             ], 422);
    //         }

    //         $imageIdsToDelete = $request->image_ids;

    //         // Fetch current images
    //         $existingImageIds = $product->image ? explode(',', $product->image) : [];

    //         // Find and delete the images from storage and database
    //         foreach ($imageIdsToDelete as $imgId) {
    //             $upload = UploadModel::find($imgId);
    //             if ($upload) {
    //                 // Remove file from storage
    //                 $filePath = str_replace(asset('storage/'), '', $upload->file_url);
    //                 if (Storage::disk('public')->exists($filePath)) {
    //                     Storage::disk('public')->delete($filePath);
    //                 }

    //                 // Delete from uploads table
    //                 $upload->delete();
    //             }
    //         }

    //         // Remove deleted IDs from product images
    //         $updatedImageIds = array_diff($existingImageIds, $imageIdsToDelete);
    //         $product->image = implode(',', $updatedImageIds);
    //         $product->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Selected product images deleted successfully!',
    //             'remaining_images' => $product->image,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // import images
    public function importProductImagesFromCSV()
    {
        try {
            // Start DB Transaction
            DB::beginTransaction();

            // Clear old data before import
            UploadModel::truncate();

            // Define CSV file path
            $filePath = public_path('storage/uploads/migration_exports/product_images.csv');

            if (!file_exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'CSV file not found!'], 404);
            }

            // Define the correct image directory (physical location)
            $imageDirectory = public_path('storage/uploads/products/product_images'); 

            // Read CSV file
            $file = fopen($filePath, 'r');
            $uploadIdsByProduct = [];
            $existingUploads = [];

            // Skip the first row (headers)
            fgetcsv($file);

            while (($row = fgetcsv($file, 1000, ",")) !== false) {
                if (count($row) < 4) continue; // Skip malformed rows

                [$id, $productId, $imageUrl, $status] = $row;

                // Extract file details
                $fileNameWithExt = basename($imageUrl);
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                $fileExt = pathinfo($fileNameWithExt, PATHINFO_EXTENSION);

                // Construct the correct physical file path (from product_images folder)
                $serverFilePath = $imageDirectory . '/' . $fileNameWithExt;
                // Build an accessible URL using the same folder structure
                $accessibleFileUrl = "storage/uploads/products/product_images/" . $fileNameWithExt;

                if (!file_exists($serverFilePath)) {
                    continue; // Skip missing images
                }

                $fileSize = filesize($serverFilePath);

                // Check if this image is already stored for the product
                if (isset($existingUploads[$productId]) && in_array($fileNameWithExt, $existingUploads[$productId])) {
                    continue; // Skip duplicate image for the same product
                }

                // Store in `t_uploads`
                $uploadId = DB::table('t_uploads')->insertGetId([
                    'file_name' => $fileName,
                    'file_ext' => $fileExt,
                    'file_url' => $accessibleFileUrl, // Now storing the accessible URL
                    'file_size' => $fileSize,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Track inserted images per product to avoid duplicates
                $existingUploads[$productId][] = $fileNameWithExt;

                // Check if product exists in `t_products`
                $productExists = DB::table('t_products')->where('id', $productId)->exists();

                if ($productExists) {
                    $uploadIdsByProduct[$productId][] = $uploadId;
                }
            }
            fclose($file);

            // Update `t_products.image` with comma-separated upload IDs
            foreach ($uploadIdsByProduct as $productId => $uploadIds) {
                DB::table('t_products')->where('id', $productId)->update([
                    'image' => implode(',', $uploadIds)
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Product images migrated successfully!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update Product Status
     */
    public function updateProductStatus(Request $request)
    {
        try {
            // Validate Input
            $request->validate([
                'product' => 'required|integer|exists:t_products,id', // Ensure product exists
                'status' => 'required|string|in:active,in-active,sold' // Ensure valid status
            ]);

            // Find Product
            $product = ProductModel::find($request->product);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ], 200);
            }

            // Update Product Status
            $product->status = $request->status;
            $product->save();

            // Return Success Response
            return response()->json([
                'success' => true,
                'message' => 'Product status updated successfully!',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'status' => $product->status
                ]
            ], 200);

        } catch (\Exception $e) {
            // Handle Errors
            return response()->json([
                'success' => false,
                'message' => 'Error updating product status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getUnits()
    {
        return response()->json([
            'success' => true,
            'message' => 'Units fetched successfully!',
            'data' => [
                'LTS',
                'UNIT',
                'MM',
                'KG',
                'CM',
                'M',
                'QUINTAL',
                'POUNDS',
                'TON',
                'BOX',
                'PALLETS',
                'GRAM',
                'FEET',
                'YARD',
                'ACRE',
                'HECTARE',
                'CONTAINER',
                'PIECES',
                'CUBIC CENTIMETER',
                'SQUARE METER',
                'SQUARE FEET',
                'SQUARE YARDS',
                'BUNDLE'
            ]
            
        ], 200);
    }

    public function admin_fetchProducts(Request $request)
    {
        try {
            $query = ProductModel::with([
                'industryDetails:id,name',
                'subIndustryDetails:id,name',
                'user:id,name'
            ]);

            if ($request->filled('product_name')) {
                $query->where('product_name', 'LIKE', '%' . $request->product_name . '%');
            }

            if ($request->filled('industry')) {
                $industries = explode(',', $request->industry);
                $query->whereIn('industry', $industries);
            }

            if ($request->filled('sub_industry')) {
                $subIndustries = explode(',', $request->sub_industry);
                $query->whereIn('sub_industry', $subIndustries);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('user')) {
                $userIds = explode(',', $request->user);
                $query->whereIn('user_id', $userIds);
            }

            $min = $request->input('min_amount', 0);
            $max = $request->input('max_amount', ProductModel::max('selling_price'));
            $query->whereBetween('selling_price', [$min, $max]);

            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            // Clone query for accurate count before pagination
            $totalCount = (clone $query)->count();

            $products = $query->offset($offset)->limit($limit)->get();

            // Map image ids to URLs and format final response
            $formatted = $products->map(function ($product) {
                // Resolve image URLs
                $imageUrls = [];
                if (!empty($product->image)) {
                    $imageIds = explode(',', $product->image);
                    $imageUrls = UploadModel::whereIn('id', $imageIds)
                        ->pluck('file_url')
                        ->map(fn($path) => url($path))
                        ->values()
                        ->toArray();
                }

                return [
                    'id'              => $product->id,
                    'product_id'      => $product->product_id,
                    'product_name'    => $product->product_name,
                    'original_price'  => $product->original_price,
                    'selling_price'   => $product->selling_price,
                    'offer_quantity'  => $product->offer_quantity,
                    'minimum_quantity'=> $product->minimum_quantity,
                    'unit'            => $product->unit,
                    'description'     => $product->description,
                    'dimensions'      => $product->dimensions,
                    'validity'        => $product->validity,
                    'status'          => $product->status,
                    'image'           => $imageUrls,
                    'industry'        => $product->industryDetails 
                        ? ['id' => $product->industryDetails->id, 'name' => $product->industryDetails->name] 
                        : null,
                    'sub_industry'    => $product->subIndustryDetails 
                        ? ['id' => $product->subIndustryDetails->id, 'name' => $product->subIndustryDetails->name] 
                        : null,
                    'user'            => $product->user 
                        ? ['id' => $product->user->id, 'name' => $product->user->name] 
                        : null,
                ];
            });

            return response()->json([
                'code'        => 200,
                'success'     => true,
                'data'        => $formatted,
                'total_count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function toggleProductStatus(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:t_products,id',
                'product_status' => 'required|in:active,in-active,sold',
            ]);

            $product = ProductModel::find($request->product_id);
            $product->status = $request->product_status;
            $product->save();

            return response()->json([
                'code' => 200,
                'success' => true,
                'message' => 'Product status updated successfully.',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
