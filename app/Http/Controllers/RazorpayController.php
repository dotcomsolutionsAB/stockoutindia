<?php

namespace App\Http\Controllers;
use App\Models\RazorpayOrdersModel;
use App\Models\RazorpayPaymentsModel;
use App\Models\ProductModel;
use App\Models\CouponModel;
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
             // Ensure $amount is numeric (Fix the error)
            $amount = (float) $amount; 

            // Prepare order data
            $orderData = [
                'amount' => $amount * 100, // Convert to paise
                'currency' => 'INR',
                'receipt' => 'order_receipt_' . time(),
                'payment_capture' => 1, // Auto capture payment
            ];

            // Create Order in Razorpay
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
            // Validate input
            $request->validate([
                'payment_amount' => 'required|numeric|min:1',
                'product' => 'required|integer|exists:t_products,id',
                'coupon' => 'nullable|string|exists:t_coupons,name',
                'comments' => 'nullable|string',
            ]);

            // Authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $couponId = null;
            $finalAmount = $request->payment_amount;

            // Check coupon
            if ($request->coupon) {
                $coupon = CouponModel::where('name', $request->coupon)->first();

                if ($coupon && $coupon->is_active === '1') {
                    $finalAmount = max(0, $finalAmount - $coupon->value);
                    $couponId = $coupon->id;
                }
            }

             // **New Check**: If the final amount is less than 1 INR, return an error message
            if ($finalAmount < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum 1 INR needs to be paid.',
                ], 400);
            }

            // Create Razorpay order using final amount
            $razorpayResponse = $this->createOrder($finalAmount);

            // Save order
            $payment = RazorpayOrdersModel::create([
                'user' => $user->id,
                'product' => $request->product,
                'payment_amount' => $finalAmount, // Correct amount after discount
                'razorpay_order_id' => $razorpayResponse['order_id'],
                'status' => $razorpayResponse['order']['status'],
                'comments' => $request->comments,
                'date' => now()->toDateString(),
                'coupon' => $couponId, // Save coupon if applicable
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'data' => $payment->makeHidden(['created_at', 'updated_at']),
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
            // $payments = RazorpayOrdersModel::with('productDetails')
            // ->where('user', $userId)
            // ->get();
            $payments = RazorpayOrdersModel::with([
                'productDetails' => function ($q) {
                    $q->with([
                        'firstImage' => fn ($q) => $q->select('id', 'file_url')
                    ])->select('id', 'product_name', 'image', 'selling_price');
                }
            ])
            ->where('user', $userId)
            ->get();


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
            // Validate Input Data (Only `razorpay_payment_id` is required from frontend)
            $request->validate([
                'razorpay_payment_id' => 'required|string|max:255|unique:t_razorpay_payments,razorpay_payment_id',
                'product' => 'required|integer|exists:t_products,id'
            ]);

            // Fetch payment details from Razorpay
            $razorpayPaymentId = $request->razorpay_payment_id;
            $payment = $this->razorpay->payment->fetch($razorpayPaymentId);
            $paymentDetails = $payment->toArray(); // Convert response to array

            // Ensure the `order_id` exists in the database
            $order = RazorpayOrdersModel::where('razorpay_order_id', $paymentDetails['order_id'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found in the database!',
                ], 200);
            }

            // Insert data column-wise
            $store_payment = RazorpayPaymentsModel::create([
                'order' => $order->id, // Link payment to order
                'status' => $paymentDetails['status'], // Status from Razorpay
                'date' => now()->toDateString(), // Auto-fill current date
                'user' => Auth::user()->id, // Authenticated user ID
                'razorpay_payment_id' => $razorpayPaymentId, // Payment ID from Razorpay
                'mode_of_payment' => $paymentDetails['method'], // Payment method (UPI, Card, NetBanking)
            ]);

            // Calculate Validity (30 days from today)
            $validityDate = now()->addDays(30)->toDateString();

            // Update product validity and status to "active"
            ProductModel::where('id', $request->product)->update([
                'validity' => $validityDate,
                'status' => 'active'
            ]);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Payment stored successfully!',
                'data' => $store_payment->makeHidden(['updated_at', 'created_at']),
                'validity' => $validityDate,
            ], 201);

        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'success' => false,
                'message' => 'Error storing payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function key()
    {
        // Only return whitelisted, non-sensitive values
        $razorpayKey = config('services.razorpay.key');

        return response()->json([
            'success' => true,
            'data' => [
                'RAZORPAY_KEY' => $razorpayKey ?? null,
            ],
        ], 200);
    }
}
