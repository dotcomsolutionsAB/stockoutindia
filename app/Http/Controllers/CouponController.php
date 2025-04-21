<?php

namespace App\Http\Controllers;
use App\Models\CouponModel;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    //
    // 🔍 Fetch all or by ID
    public function index(Request $request, $id = null)
    {
        try {
            if ($request->has('id')) {
                $coupon = CouponModel::find($request->id);
                if (!$coupon) {
                    return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
                }
                return response()->json(['success' => true, 'data' => $coupon->makeHidden(['created_at', 'updated_at'])], 200);
            }

            $coupons = CouponModel::all();
            return response()->json(['success' => true, 'data' => $coupons->makeHidden(['created_at', 'updated_at'])], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ➕ Create coupon
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:t_coupons,name',
                'value' => 'required|numeric',
                'is_active' => 'required|in:0,1',
            ]);

            $coupon = new CouponModel();
            $coupon->name = $request->name;
            $coupon->value = $request->value;
            $coupon->is_active = $request->is_active;
            $coupon->save();

            return response()->json(['success' => true, 'message' => 'Coupon created successfully', 'data' => $coupon->makeHidden(['created_at', 'updated_at'])], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ✏️ Update coupon
    public function update(Request $request, $id)
    {
        try {
            $coupon = CouponModel::find($id);
            if (!$coupon) {
                return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
            }

            // ✅ Validation including unique check excluding current ID
            $request->validate([
                'name' => [
                        'sometimes', 'string',
                        function ($attribute, $value, $fail) use ($id) {
                            $exists = CouponModel::whereRaw('LOWER(name) = ?', [strtolower($value)])
                                ->where('id', '!=', $id)
                                ->exists();

                            if ($exists) {
                                $fail("The $attribute has already been taken.");
                            }
                        }
                    ],
                'value' => 'sometimes|required|numeric',
                'value_type' => 'sometimes|required|string',
                'is_active' => 'sometimes|required|in:0,1',
            ]);

            if ($request->has('name')) $coupon->name = $request->name;
            if ($request->has('value')) $coupon->value = $request->value;
            if ($request->has('value_type')) $coupon->value_type = $request->value_type;
            if ($request->has('is_active')) $coupon->is_active = $request->is_active;

            $coupon->save();

            return response()->json(['success' => true, 'message' => 'Coupon updated successfully', 'data' => $coupon->makeHidden(['created_at', 'updated_at'])], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ❌ Delete coupon
    public function destroy($id)
    {
        try {
            $coupon = CouponModel::find($id);
            if (!$coupon) {
                return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
            }

            $coupon->delete();

            return response()->json(['success' => true, 'message' => 'Coupon deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
