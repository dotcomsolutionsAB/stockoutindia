<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IndustryModel;
use App\Models\UploadModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class IndustryController extends Controller
{
    //create
    public function createIndustry(Request $request)
    {
        try {
            // Validate Request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:t_industries,slug|max:255',
                'desc' => 'nullable|string',
                'sequence' => 'integer|nullable',
            ]);

            // Create Industry column-wise
            $industry = new IndustryModel();
            $industry->name = $validated['name'];
            $industry->slug = $validated['slug'];
            $industry->desc = $validated['desc'] ?? null;
            $industry->sequence = $validated['sequence'] ?? 0;
            $industry->save();

            return response()->json([
                'success' => true,
                'message' => 'Industry created successfully!',
                'data' => $industry->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // view
    public function getIndustries($id = null)
    {
        try {
            // Fetch industries with sub-industries
            if ($id) {
                $industry = IndustryModel::with('subIndustries:id,industry,name')->find($id);
    
                if (!$industry) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Industry not found!',
                    ], 404);
                }
    
                // Transform single industry response
                $formattedIndustry = [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'desc' => $industry->desc,
                    'sequence' => $industry->sequence,
                    'industry_image' => $industry->image
                        ? secure_url(optional(UploadModel::find($industry->image))->file_url)
                        : null,
                    'sub_industries' => $industry->subIndustries->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'image' => $sub->image
                                ? secure_url(optional(UploadModel::find($sub->image))->file_url)
                                : null,
                             // Count the products in this sub-industry using the relationship
                            'product_count' => $sub->products()->count(),
                        ];
                    }),
                ];
    
                return response()->json([
                    'success' => true,
                    'message' => 'Industry data retrieved successfully!',
                    'data' => $formattedIndustry,
                ], 200);
            }
    
            // Fetch all industries
            $industries = IndustryModel::with('subIndustries:id,industry,name,image')->get();

            // Transform all industries response
            $formattedIndustries = $industries->map(function ($industry) {
                return [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'desc' => $industry->desc,
                    'sequence' => $industry->sequence,
                    'industry_image' => $industry->image
                        ? secure_url(optional(UploadModel::find($industry->image))->file_url)
                        : null,
                    'sub_industries' => $industry->subIndustries->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'image' => $sub->image
                                ? secure_url(optional(UploadModel::find($sub->image))->file_url)
                                : null,
                            // Count the products in this sub-industry using the relationship
                            'product_count' => $sub->products()->count(),
                        ];
                    }),
                ];
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Industry data retrieved successfully!',
                'data' => $formattedIndustries,
                'total_record' => $industries->count(),
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
    public function updateIndustry(Request $request, $id)
    {
        try {
            // Find industry
            $industry = IndustryModel::find($id);

            if (!$industry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Industry not found!',
                ], 404);
            }

            // Validate Input
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => "sometimes|string|unique:t_industries,slug,{$id}|max:255",
                'desc' => 'nullable|string',
                'sequence' => 'integer|nullable',
            ]);

            // Column-wise update
            if ($request->has('name')) {
                $industry->name = $validated['name'];
            }
            if ($request->has('slug')) {
                $industry->slug = $validated['slug'];
            }
            if ($request->has('desc')) {
                $industry->desc = $validated['desc'];
            }
            if ($request->has('sequence')) {
                $industry->sequence = $validated['sequence'];
            }

            $industry->save();

            return response()->json([
                'success' => true,
                'message' => 'Industry updated successfully!',
                'data' => $industry->makeHidden(['id', 'created_at', 'updated_at']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // delete
    public function deleteIndustry($id)
    {
        try {
            // Find industry
            $industry = IndustryModel::find($id);

            if (!$industry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Industry not found!',
                ], 404);
            }

            // Delete industry
            $industry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Industry deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
