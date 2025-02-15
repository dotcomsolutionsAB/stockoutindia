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
                'city' => $request->city,
                'state_id' => $request->state_id,
                'status' => $request->status ?? 'active',
                'description' => $request->description,
                'dimensions' => $request->dimensions,
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
    // public function fetchProducts($id = null)
    // {
    //     try {
    //         if ($id) {
    //             // $product = ProductModel::find($id);

    //             $product = ProductModel::with([
    //                 'user:id,name,mobile',
    //                 'industryDetails:id,name',
    //                 'subIndustryDetails:id,name'
    //             ])->find($id);
    
    //             if (!$product) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Product not found!',
    //                 ], 404);
    //             }

    //             // Parse image column
    //             $uploadIds = $product->image ? explode(',', $product->image) : [];
    //             // Retrieve file URLs from `uploads` table
    //             $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

    //             // You can return them as an array of file_urls
    //             $imageUrls = [];
    //             foreach ($uploadIds as $uid) {
    //                 if (isset($uploads[$uid])) {
    //                     $imageUrls[] = url($uploads[$uid]);
    //                 }
    //             }

    //             // Overwrite product->image with array of file objects
    //             $product->image = $imageUrls; // attach to response

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Product details fetched successfully!',
    //                 'data' => $product->makeHidden(['id', 'created_at', 'updated_at']),
    //             ], 200);
    //         } else {
    //             // $products = ProductModel::all();
    //             // Fetch all products with relationships
    //             $products = ProductModel::with([
    //                 'user:id,name,mobile',
    //                 'industryDetails:id,name',
    //                 'subIndustryDetails:id,name'
    //             ])->get();


    //             // For each product, parse the images
    //             $products->transform(function ($prod) {
    //                 $uploadIds = $prod->image ? explode(',', $prod->image) : [];
    //                 $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

    //                 $imageUrls = [];
    //                 foreach ($uploadIds as $uid) {
    //                     if (isset($uploads[$uid])) {
    //                         $imageUrls[] = url($uploads[$uid]);
    //                     }
    //                 }

    //                 // Overwrite product->image with array of file objects
    //                 $prod->image = $imageUrls;
    //                 return $prod->makeHidden(['id', 'created_at', 'updated_at']);
    //             });

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'All products fetched successfully!',
    //                 'data' => $products->makeHidden(['id', 'created_at', 'updated_at']),
    //                 'total_record' => count($products),
    //             ], 200);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function fetchProducts(Request $request, $id = null)
    // {
    //     try {
    //         if ($id) {
    //             // Fetch a single product with related user, industry, and sub-industry
    //             $product = ProductModel::with([
    //                 'user:id,name,phone',
    //                 'industryDetails:id,name',
    //                 'subIndustryDetails:id,name'
    //             ])->find($id);

    //             if (!$product) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Product not found!',
    //                 ], 404);
    //             }

    //             // Parse images
    //             $uploadIds = $product->image ? explode(',', $product->image) : [];
    //             $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

    //             $product->image = array_map(fn($uid) => isset($uploads[$uid]) ? url($uploads[$uid]) : null, $uploadIds);

    //             // Attach user details
    //             $responseData = [
    //                 'user' => [
    //                     'name' => optional($product->user)->name,
    //                     'phone' => optional($product->user)->phone,
    //                 ],
    //                 'industry' => optional($product->industryDetails)->name,
    //                 'sub_industry' => optional($product->subIndustryDetails)->name,
    //             ] + $product->toArray();

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Product details fetched successfully!',
    //                 'data' => collect($responseData)->except(['id', 'created_at', 'updated_at']),
    //             ], 200);
    //         }

    //         // Fetch input filters
    //         $search = $request->input('search'); // Search in product_name, city, user->name
    //         $industryIds = $request->input('industry') ? explode(',', $request->input('industry')) : [];
    //         $subIndustryIds = $request->input('sub_industry') ? explode(',', $request->input('sub_industry')) : [];
    //         $userIds = $request->input('user_id') ? explode(',', $request->input('user_id')) : [];
    //         $cities = $request->input('city') ? explode(',', $request->input('city')) : [];
    //         $stateIds = $request->input('state_id') ? explode(',', $request->input('state_id')) : [];

    //         $limit = $request->input('limit', 10); // Default limit
    //         $offset = $request->input('offset', 0); // Default offset

    //         // // Fetch all products with relationships
    //         // $products = ProductModel::with([
    //         //     'user:id,name,phone',
    //         //     'industryDetails:id,name',
    //         //     'subIndustryDetails:id,name'
    //         // ])->get();

    //         // Query products with relationships
    //         $query = ProductModel::with([
    //             'user:id,name,mobile',
    //             'industryDetails:id,name',
    //             'subIndustryDetails:id,name'
    //         ]);

    //         // Search in product_name, city, and user->name
    //         if (!empty($search)) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('product_name', 'like', "%{$search}%")
    //                 ->orWhere('city', 'like', "%{$search}%")
    //                 ->orWhereHas('user', function ($q) use ($search) {
    //                     $q->where('name', 'like', "%{$search}%");
    //                 });
    //             });
    //         }

    //         // Apply filters if provided
    //         if (!empty($industryIds)) {
    //             $query->whereIn('industry', $industryIds);
    //         }
    //         if (!empty($subIndustryIds)) {
    //             $query->whereIn('sub_industry', $subIndustryIds);
    //         }
    //         if (!empty($userIds)) {
    //             $query->whereIn('user_id', $userIds);
    //         }
    //         if (!empty($cities)) {
    //             $query->whereIn('city', $cities);
    //         }
    //         if (!empty($stateIds)) {
    //             $query->whereIn('state_id', $stateIds);
    //         }

    //         // Apply pagination
    //         $totalRecords = $query->count();
    //         $products = $query->offset($offset)->limit($limit)->get();

    //         // if ($products->isEmpty()) {
    //         //     return response()->json([
    //         //         'success' => true,
    //         //         'message' => 'No products found!',
    //         //         'data' => [],
    //         //         'total_record' => 0,
    //         //     ], 200);
    //         // }

    //         if ($products->isEmpty()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'No products found!',
    //                 'data' => [],
    //                 'total_record' => 0,
    //             ], 200);
    //         }

    //         // // Get all image IDs in one go
    //         // $allImageIds = collect($products)->flatMap(fn($p) => explode(',', $p->image ?? ''))->unique()->filter();
    //         // $uploads = UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

    //         // // Transform each product
    //         // $products->transform(function ($prod) use ($uploads) {
    //         //     $uploadIds = $prod->image ? explode(',', $prod->image) : [];
    //         //     $prod->image = array_map(fn($uid) => isset($uploads[$uid]) ? url($uploads[$uid]) : null, $uploadIds);

    //         //     return collect([
    //         //         'user' => [
    //         //             'name' => optional($prod->user)->name,
    //         //             'phone' => optional($prod->user)->phone,
    //         //         ],
    //         //         'industry' => optional($prod->industryDetails)->name,
    //         //         'sub_industry' => optional($prod->subIndustryDetails)->name,
    //         //     ] + $prod->toArray())->except(['id', 'created_at', 'updated_at']);
    //         // });

    //          // Get all image IDs in one go
    //         $allImageIds = collect($products)->flatMap(fn($p) => explode(',', $p->image ?? ''))->unique()->filter();
    //         $uploads = UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

    //         // Transform each product
    //         $products->transform(function ($prod) use ($uploads) {
    //             $uploadIds = $prod->image ? explode(',', $prod->image) : [];
    //             $prod->image = array_map(fn($uid) => isset($uploads[$uid]) ? url($uploads[$uid]) : null, $uploadIds);

    //         return collect([
    //             'user' => [
    //                 'name' => optional($prod->user)->name,
    //                 'mobile' => optional($prod->user)->mobile,
    //             ],
    //             'industry' => optional($prod->industryDetails)->name,
    //             'sub_industry' => optional($prod->subIndustryDetails)->name,
    //         ] + $prod->toArray())->except(['id', 'created_at', 'updated_at']);
    //     });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'All products fetched successfully!',
    //             'data' => $products,
    //             'total_record' => $products->count(),
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function fetchProducts(Request $request, $id = null)
{
    try {
        if ($id) {
            // Fetch a single product with related user, industry, and sub-industry
            $product = ProductModel::with([
                'user:id,name,phone',
                'industryDetails:id,name',
                'subIndustryDetails:id,name'
            ])->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found!',
                ], 404);
            }

            // Parse images
            $uploadIds = $product->image ? explode(',', $product->image) : [];
            $uploads = UploadModel::whereIn('id', $uploadIds)->pluck('file_url', 'id');

            $product->image = array_map(fn($uid) => isset($uploads[$uid]) ? url($uploads[$uid]) : null, $uploadIds);

            // Format response correctly
            $responseData = [
                'user' => [
                    'name' => optional($product->user)->name,
                    'phone' => optional($product->user)->phone,
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

        // Fetch input filters
        $search = $request->input('search');
        $industryIds = $request->input('industry') ? explode(',', $request->input('industry')) : [];
        $subIndustryIds = $request->input('sub_industry') ? explode(',', $request->input('sub_industry')) : [];
        $userIds = $request->input('user_id') ? explode(',', $request->input('user_id')) : [];
        $cities = $request->input('city') ? explode(',', $request->input('city')) : [];
        $stateIds = $request->input('state_id') ? explode(',', $request->input('state_id')) : [];

        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        // Query products with relationships
        $query = ProductModel::with([
            'user:id,name,phone',
            'industryDetails:id,name',
            'subIndustryDetails:id,name'
        ]);

        // Search in product_name, city, and user->name
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply filters if provided
        if (!empty($industryIds)) {
            $query->whereIn('industry', $industryIds);
        }
        if (!empty($subIndustryIds)) {
            $query->whereIn('sub_industry', $subIndustryIds);
        }
        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }
        if (!empty($cities)) {
            $query->whereIn('city', $cities);
        }
        if (!empty($stateIds)) {
            $query->whereIn('state_id', $stateIds);
        }

        // Apply pagination
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

        // Get all image IDs in one go
        $allImageIds = collect($products)->flatMap(fn($p) => explode(',', $p->image ?? ''))->unique()->filter();
        $uploads = UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

        // Transform each product correctly
        $products->transform(function ($prod) use ($uploads) {
            $uploadIds = $prod->image ? explode(',', $prod->image) : [];
            $prod->image = array_map(fn($uid) => isset($uploads[$uid]) ? url($uploads[$uid]) : null, $uploadIds);

            return collect([
                'user' => [
                    'name' => optional($prod->user)->name,
                    'phone' => optional($prod->user)->phone,
                ],
                'industry' => optional($prod->industryDetails)->name,  // Fixed industry name
                'sub_industry' => optional($prod->subIndustryDetails)->name,  // Fixed sub-industry name
            ] + $prod->toArray())->except(['id', 'user_id', 'industry', 'sub_industry', 'created_at', 'updated_at']);
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




    // for guest-user
    public function fetchOnlyProducts($id = null)
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
                    'data' => $product->makeHidden(['id', 'user_id', 'created_at', 'updated_at']),
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
                    return $prod->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'All products fetched successfully!',
                    'data' => $products->makeHidden(['id', 'created_at', 'updated_at']),
                    'total_record' => count($products),
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
                'unit' => 'sometimes|string|max:255', // Updated max length from 50 to 255 to match DB
                'industry' => 'sometimes|integer|exists:t_industries,id',
                'sub_industry' => 'sometimes|integer|exists:t_sub_industries,id',
                'city' => 'sometimes|nullable|string|max:255', // Added city field
                'state_id' => 'sometimes|nullable|integer|exists:t_states,id', // Added state_id validation
                'status' => 'sometimes|in:active,in-active',
                'description' => 'sometimes|nullable|string',
                'dimensions' => 'sometimes|nullable|string|max:256', // Added dimensions field
                        ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
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
