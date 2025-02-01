<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
use App\Models\UploadModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Auth;

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
                'unit' => 'required|string',
                'industry' => 'required|integer|exists:t_industries,id',
                'sub_industry' => 'required|integer|exists:t_sub_industries,id',
                'status' => 'sometimes|in:active,in-active',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
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
                'status' => $request->status ?? 'active',
                'description' => $request->description,
                // 'image' => will be empty or null initially; handled in separate method
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully!',
                'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // view
    public function fetchProducts($id = null)
    {
        try {
            if ($id) {
                $product = ProductModel::find($id);
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found!',
                    ], 404);
                }

                // Parse image column
                $uploadIds = $product->image ? explode(',', $product->image) : [];
                // Retrieve file URLs from `uploads` table
                $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

                // You can return them as an array of file_urls
                $imageUrls = [];
                foreach ($uploadIds as $uid) {
                    if (isset($uploads[$uid])) {
                        $imageUrls[] = url($uploads[$uid]);
                    }
                }

                // Overwrite product->image with array of file objects
                $product->image = $imageUrls; // attach to response

                return response()->json([
                    'success' => true,
                    'message' => 'Product details fetched successfully!',
                    'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
                ], 200);
            } else {
                $products = ProductModel::all();

                // For each product, parse the images
                $products->transform(function ($prod) {
                    $uploadIds = $prod->image ? explode(',', $prod->image) : [];
                    $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

                    $imageUrls = [];
                    foreach ($uploadIds as $uid) {
                        if (isset($uploads[$uid])) {
                            $imageUrls[] = url($uploads[$uid]);
                        }
                    }

                    // Overwrite product->image with array of file objects
                    $prod->image = $imageUrls;
                    return $prod->makeHidden(['id', 'created_at', 'updated_at']);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'All products fetched successfully!',
                    'data' => $products->makeHidden(['id', 'created_at', 'updated_at']),
                ], 200);
            }
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
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'product_name' => 'sometimes|string|max:255',
                'original_price' => 'sometimes|numeric',
                'selling_price' => 'sometimes|numeric',
                'offer_quantity' => 'sometimes|integer',
                'minimum_quantity' => 'sometimes|integer',
                'unit' => 'sometimes|string|max:50',
                'industry' => 'sometimes|integer',
                'sub_industry' => 'sometimes|integer',
                'status' => 'sometimes|in:active,in-active',
                'description' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Column-wise update
            $product->update($request->only([
                'product_name', 'original_price', 'selling_price', 'offer_quantity',
                'minimum_quantity', 'unit', 'industry', 'sub_industry', 'status', 'description'
            ]));

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
    public function uploadProductImages(Request $request, $id)
    {
        try {
            $product = ProductModel::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 404);
            }

            // Validate new files
            $validator = Validator::make($request->all(), [
                'files.*' => 'required|mimes:jpg,jpeg,png,heif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Delete old images from DB + server
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
                    // 1. Store file in "uploads/products" folder on the "public" disk
                    $path = $file->store('uploads/products', 'public');

                    // 2. Extract file details
                    $originalName = $file->getClientOriginalName(); // e.g. "photo.jpg"
                    $extension = $file->extension();                // e.g. "jpg"
                    $size = $file->getSize();                       // e.g. 123456 (bytes)

                    $upload = UploadModel::create([
                        'file_name' => $originalName,
                        'file_ext' => $extension,
                        'file_url' => asset("storage/$path"),
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

    // delete
    public function deleteProduct($id)
    {
        try {
            $product = ProductModel::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 404);
            }

            // Delete existing images
            $oldImageIds = $product->image ? explode(',', $product->image) : [];

            foreach ($oldImageIds as $imgId) {
                $upload = UploadModel::find($imgId);
                if ($upload) {
                    // Convert DB URL to actual storage path
                    $filePath = str_replace(asset('storage/'), '', $upload->file_url);
                    $filePath = 'uploads/products/' . basename($filePath); // Ensure correct path

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
}
