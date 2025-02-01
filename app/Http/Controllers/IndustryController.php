<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IndustryModel;
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
            // Fetch industries or single record by ID
            $industries = $id ? IndustryModel::find($id) : IndustryModel::all();

            if (!$industries) {
                return response()->json([
                    'success' => false,
                    'message' => 'Industry not found!',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Industry data retrieved successfully!',
                'data' => $industries->makeHidden(['created_at', 'updated_at']),
                'total_record' => count($industries),
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
