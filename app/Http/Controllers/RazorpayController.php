<?php

namespace App\Http\Controllers;
use App\Models\RazorpayOrdersModel;
use App\Models\RazorpayPaymentsModel;
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


            // ✅ Store payment details in database
            $payment = RazorpayOrdersModel::create([
                'user' => $user->id,
                'product' => $request->product,
                'payment_amount' => $request->payment_amount,
                'razorpay_order_id' => $razorpayResponse['order_id'],
                'status' => $razorpayResponse['order']['status'],
                'comments' => $request->comments,
                'date' => now()->toDateString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'data' => $payment->makeHidden(['updated_at', 'created_at'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch payments for the logged-in user (or admin-provided user_id)
     */
    public function fetchPayments(Request $request)
    {
        try {
            // Check if the user is an admin or a regular user
            $user = Auth::user();

            // If the user is an admin and the user_id is provided, fetch payments for that user
            if ($user->role == 'admin') {
                 // Validate that user_id exists in the users table if provided
                 $request->validate([
                    'user' => 'required|integer|exists:users,id', // Ensure user_id exists in the users table
                ]);

                // Get the user_id from the request
                $userId = $request->user;
            } else {
                // For regular users, use the authenticated user's ID
                $userId = $user->id;
            }

            // Fetch payments for the specified user
            $payments = RazorpayOrdersModel::where('user', $userId)->get();

            // Return response with payment data
            return response()->json([
                'success' => true,
                'message' => 'Payments fetched successfully!',
                'data' => $payments->makeHidden(['updated_at', 'created_at']),
            ], 200);

        } catch (\Exception $e) {
            // Handle any exceptions and return error message
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store Razorpay Payment Details
     */
    public function storePayment(Request $request)
    {
        try {
            // ✅ Validate Input Data
            $request->validate([
                'order' => 'required|integer|exists:t_razorpay_orders,id', // Ensure order exists
                'status' => 'required|string|max:255',
                'razorpay_payment_id' => 'required|string|max:255',
                'mode_of_payment' => 'required|string|max:255',
            ]);

            // ✅ Insert data column-wise
            $store_payment = RazorpayPaymentsModel::create([
                'order' => $request->order, // Order ID from frontend
                'status' => $request->status, // Status from frontend
                'date' => now()->toDateString(), // Current date
                'user' => Auth::user()->id, // Logged-in user
                'razorpay_payment_id' => $request->razorpay_payment_id, // Payment ID from Razorpay
                'mode_of_payment' => $request->mode_of_payment, // Payment method from frontend
            ]);

            // ✅ Return success response
            return response()->json([
                'success' => true,
                'message' => 'Payment stored successfully!',
                'data' => $store_payment,
            ], 201);

        } catch (\Exception $e) {
            // ✅ Handle exceptions
            return response()->json([
                'success' => false,
                'message' => 'Error storing payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
