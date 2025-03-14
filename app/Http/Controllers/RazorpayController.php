<?php

namespace App\Http\Controllers;
use App\Models\RazorpayOrdersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    //
    protected $razorpay;

    public function __construct()
    {
        // Replace with your Razorpay API key and secret
        $this->razorpay = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    }

    /**
     * Create an order in Razorpay
     */
    public function createOrder($amount)
    {
        try {
             // ✅ Ensure $amount is numeric (Fix the error)
            $amount = (float) $amount; 

            // ✅ Prepare order data
            $orderData = [
                'amount' => $amount * 100, // Convert to paise
                'currency' => 'INR',
                'receipt' => 'order_receipt_' . time(),
                'payment_capture' => 1, // Auto capture payment
            ];

            // ✅ Create Order in Razorpay
            $order = $this->razorpay->order->create($orderData);
            $orderId = $order['id'];

            return [
                'success' => true,
                'order_id' => $orderId,
                'order' => $order->toArray(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating Razorpay order: ' . $e->getMessage(),
            ];
        }
    }

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

            // ✅ Pass the payment amount directly to createOrder
            $razorpayResponse = $this->createOrder($request->payment_amount); // Pass amount directly


            // ✅ Extract Razorpay Order ID
            $razorpayOrderId = $razorpayResponse['order_id'];
            $status = $razorpayResponse['status']; // Default status for Razorpay order

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
