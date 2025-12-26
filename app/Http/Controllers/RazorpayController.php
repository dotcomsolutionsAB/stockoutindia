<?php

namespace App\Http\Controllers;
use App\Models\RazorpayOrdersModel;
use App\Models\RazorpayPaymentsModel;
use App\Models\ProductModel;
use App\Models\CouponModel;
use App\Models\UploadModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ProductListedConfirmationMail;
use App\Models\User;

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

            // Fetch payments for the specified user with product details
            $payments = RazorpayOrdersModel::with(['productDetails' => function ($query) {
                $query->with([
                    'user:id,name,phone,city',
                    'subIndustryDetails:id,name'
                ])->where('is_delete', '0');
            }])
            ->where('user', $userId)
            ->get();

            // Get all image IDs from the products in one go
            $allImageIds = $payments->pluck('productDetails.image')
                ->flatMap(function ($image) {
                    return $image ? explode(',', $image) : [];
                })->unique()->filter();

            // Fetch file URLs for all image IDs
            $uploads = $allImageIds->isEmpty()
                ? collect()
                : UploadModel::whereIn('id', $allImageIds)->pluck('file_url', 'id');

            // Transform payments to include file_url instead of image IDs
            $payments->transform(function ($payment) use ($uploads) {
                if ($payment->productDetails) {
                    $uploadIds = $payment->productDetails->image ? explode(',', $payment->productDetails->image) : [];
                    $payment->productDetails->image = array_map(
                        fn($uid) => isset($uploads[$uid]) ? secure_url($uploads[$uid]) : null,
                        $uploadIds
                    );
                }
                return $payment->makeHidden(['updated_at', 'created_at']);
            });

            // Return response with payment data
            return response()->json([
                'success' => true,
                'message' => 'Payments fetched successfully!',
                'data' => $payments,
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

            // ✅ Only proceed if payment is actually successful
            if (($paymentDetails['status'] ?? '') !== 'captured') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not captured yet.',
                    'data' => [
                        'razorpay_status' => $paymentDetails['status'] ?? null
                    ]
                ], 400);
            }

            // Ensure the `order_id` exists in the database
            $order = RazorpayOrdersModel::where('razorpay_order_id', $paymentDetails['order_id'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found in the database!',
                ], 200);
            }

            // ✅ Security: ensure the order is for the same product passed in request
            if ((int)$order->product !== (int)$request->product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product does not match this payment order.',
                ], 400);
            }

            // ✅ Optional: ensure the order belongs to current user
            if ((int)$order->user !== (int)Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized order access.',
                ], 403);
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

            // Send Product Listed Confirmation Email
            try {
                $user = Auth::user();
                $product = ProductModel::find($request->product);

                if ($user && $user->email && $product) {
                    Mail::to($user->email)->send(
                        new ProductListedConfirmationMail(
                            $product,
                            $user,
                            $validityDate,
                            $razorpayPaymentId,
                            $paymentDetails['method'] ?? 'NA'
                        )
                    );
                }
            } catch (\Exception $mailEx) {
                Log::error('ProductListedConfirmationMail failed: ' . $mailEx->getMessage());
            }

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
