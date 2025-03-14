<?php

namespace App\Http\Controllers;
use App\Models\RazorpayOrdersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RazorpayController extends Controller
{
    //
    /**
     * Handle payment and store details after Razorpay order creation
     */
    public function processPayment(Request $request)
    {
        try {
            // ✅ Validate the incoming request
            $request->validate([
                'payment_amount' => 'required|numeric|min:1',
                'product' => 'required|integer|exists:t_products,id',
                'comments' => 'nullable|string',
            ]);

            // ✅ Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // ✅ Call Razorpay Order Creation API
            $razorpayResponse = $this->createOrder(new Request([
                'amount' => $request->payment_amount,
                'currency' => 'INR',
                'receipt' => 'order_receipt_' . time(),
            ]));

            // ✅ Decode response
            $razorpayData = json_decode($razorpayResponse->getContent(), true);
            if (!$razorpayData['success']) {
                return response()->json(['success' => false, 'message' => 'Razorpay order failed'], 500);
            }

            // ✅ Extract Razorpay Order ID
            $razorpayOrderId = $razorpayData['order_id'];
            $status = 'created'; // Default status for Razorpay order

            // ✅ Store payment details in database
            $payment = RazorpayOrdersModel::create([
                'user' => $user->id,
                'product' => $request->product_id,
                'payment_amount' => $request->payment_amount,
                'razorpay_order_id' => $razorpayOrderId,
                'status' => $status,
                'comments' => $request->comments,
                'date' => now()->toDateString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'data' => $payment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
