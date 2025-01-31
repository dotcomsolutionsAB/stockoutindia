<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubIndustryModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class SubIndustryController extends Controller
{
    //create
    public function createSubIndustry(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:t_industries,slug|max:255',
                'industry' => 'required|integer|exists:t_industries,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $industry = SubIndustryModel::create([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'industry' => $request->input('industry'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Industry created successfully!',
                'data' => $industry
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // view
    public function getSubIndustries($id = null)
    {
        try {
            if ($id) {
                $industry = SubIndustryModel::find($id);
                if (!$industry) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Industry not found!',
                    ], 404);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Industry retrieved successfully!',
                    'data' => $industry,
                ], 200);
            }

            $industries = SubIndustryModel::all();
            return response()->json([
                'success' => true,
                'message' => 'Industries fetched successfully!',
                'data' => $industries,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching industries: ' . $e->getMessage(),
            ], 500);
        }
    }

    // edit
    public function updateSubIndustry(Request $request, $id)
    {
        try {
            $industry = SubIndustryModel::find($id);
            if (!$industry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Industry not found!',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255|unique:t_industries,slug,' . $id,
                'industry' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $industry->update($request->only(['name', 'slug', 'industry']));

            return response()->json([
                'success' => true,
                'message' => 'Industry updated successfully!',
                'data' => $industry,
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // delete
    public function deleteSubIndustry($id)
    {
        try {
            $industry = SubIndustryModel::find($id);
            if (!$industry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Industry not found!',
                ], 404);
            }

            $industry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Industry deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting industry: ' . $e->getMessage(),
            ], 500);
        }
    }
}
