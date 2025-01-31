<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
use App\Models\UploadModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
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
                $imageFiles = [];
                foreach ($uploadIds as $uid) {
                    if (isset($uploads[$uid])) {
                        $imageFiles[] = [
                            'id' => $uid,
                            'file_url' => $uploads[$uid],
                        ];
                    }
                }

                $product->image_files = $imageFiles; // attach to response

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

                    $imageFiles = [];
                    foreach ($uploadIds as $uid) {
                        if (isset($uploads[$uid])) {
                            $imageFiles[] = [
                                'id' => $uid,
                                'file_url' => $uploads[$uid],
                            ];
                        }
                    }
                    $prod->image_files = $imageFiles;
                    return $prod;
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
    public function updateProductImages(Request $request, $id)
    {
        try {
            $product = ProductModels::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 404);
            }

            // Validate new files
            $validator = Validator::make($request->all(), [
                'files.*' => 'required|mimes:jpg,jpeg,png|max:2048',
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
                    // Delete from server
                    Storage::delete($upload->file_url);
                    // Delete from DB
                    $upload->delete();
                }
            }

            $uploadIds = [];
            // Handle new files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Store file in "uploads" disk/folder
                    $path = $file->store('uploads', 'public');
                    // Create DB record
                    $upload = UploadModel::create(['file_url' => $path]);
                    $uploadIds[] = $upload->id;
                }
            }

            // Update product image column with new comma separated IDs
            $product->image = implode(',', $uploadIds);
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product images updated successfully!',
                'data' => $product
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
                    Storage::delete($upload->file_url);
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
