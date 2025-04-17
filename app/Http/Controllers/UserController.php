<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RazorpayOrdersModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // create
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'google_id' => 'required|string',
                'role' => ['required', Rule::in(['admin', 'user'])],
                'phone' => 'required|string|unique:users,phone',

                // Make `gstin` optional; if present, must be unique
                'gstin' => 'nullable|string|unique:users,gstin',

                // If `gstin` is present => these are optional,
                // otherwise they are required.
                'name' => 'required_without:gstin|string|max:255',
                'company_name' => 'required_without:gstin|string|max:255',
                'address' => 'required_without:gstin|string|max:255',
                'pincode' => 'required_without:gstin|string|max:10',
                'city' => 'required_without:gstin|string',
                'state' => 'required_without:gstin|integer|exists:t_states,id',
                'industry' => 'nullable|string',
                'sub_industry' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'google_id' => $request->google_id,
                'role' => $request->role,
                'username' => $request->phone, // Store phone in username
                'phone' => $request->phone,
                'is_active' => "1",
                'company_name' => $request->company_name,
                'address' => $request->address,
                'pincode' => $request->pincode,
                'city' => $request->city,
                'state' => $request->state,
                'gstin' => $request->gstin,
                'industry' => $request->industry,
                'sub_industry' => $request->sub_industry,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully!',
                'data' => $user->makeHidden(['id', 'created_at', 'updated_at']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // view
    public function viewUsers($id = null)
    {
        try {
            if ($id) {
                $user = User::find($id);
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found!',
                    ], 200);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'User details fetched successfully!',
                    'data' => $user,
                ], 200);
            } else {
                $users = User::all();
                return response()->json([
                    'success' => true,
                    'message' => 'All users fetched successfully!',
                    'data' => $users,
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
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found!',
                ], 200);
            }

            $validatedData =  $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:6',
                'role' => ['sometimes', Rule::in(['admin', 'student', 'teacher'])],
                'phone' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
                'company_name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'pincode' => 'sometimes|string|max:10',
                'city' => 'sometimes|integer',
                'state' => 'sometimes|integer',
                'gstin' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
                'industry' => 'nullable|string',
                'sub_industry' => 'nullable|string',
            ]);

            // Check if password needs to be hashed
            if ($request->filled('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            // Ensure phone update also updates username
            if ($request->has('phone')) {
                $user->username = $request->phone;
            }

            // $user->update($request->only([
            //     'name', 'email', 'password', 'role', 'phone', 
            //     'company_name', 'address', 'pincode', 'city', 'state', 'gstin'
            // ]));

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'data' => $user->makeHidden(['email_verified_at', 'otp', 'expires_at', 'company_name', 'created_at', 'updated_at']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // delete
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found!',
                ], 200);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Show Privacy Policy
    public function privacyPolicy()
    {
        return view('static.privacy_policy');
    }

    // Show Terms & Conditions
    public function termsConditions()
    {
        return view('static.terms_conditions');
    }

    // Show Refund Policy
    public function refundPolicy()
    {
        return view('static.refund_policy');
    }

    public function getFaqsJson()
    {
        $faqs = [
            [
                'question' => 'What is Stockout?',
                'answer' => 'Stockout is a platform that connects buyers and sellers for seamless product listing, discovery, and purchasing.'
            ],
            [
                'question' => 'How do I list a product for sale?',
                'answer' => 'You can list a product by signing into your seller account, clicking "Add Product", and filling out the required details along with images and price.'
            ],
            [
                'question' => 'Are listing fees refundable?',
                'answer' => 'No, listing fees are non-refundable as per our Refund Policy.'
            ],
            [
                'question' => 'Does Stockout offer buyer protection?',
                'answer' => 'Stockout does not take responsibility for disputes between buyers and sellers. Please review the product and seller details carefully before purchasing.'
            ],
            [
                'question' => 'How long is my data stored?',
                'answer' => 'User data is securely stored for one year and can be deleted upon request by contacting our support team.'
            ],
            [
                'question' => 'Can I advertise my product on social media through Stockout?',
                'answer' => 'Yes, you can opt-in for premium marketing services like Instagram and Facebook ads while listing your product.'
            ],
        ];

        return response()->json([
            'status' => true,
            'data' => $faqs
        ]);
    }

    public function fetchGstDetails(Request $request)
    {
        $request->validate([
            'gstin' => 'required|string'
        ]);

        try {
            $gstin = $request->gstin;
            $apiKey = env('APPYFLOW_API_KEY');

            $response = Http::get("https://appyflow.in/api/verifyGST", [
                'gstNo'     => $gstin,
                'key_secret'=> $apiKey,
            ]);

            // If the HTTP request is successful...
            if ($response->successful()) {
                $data = $response->json();

                // Check if taxpayerInfo exists (valid GSTIN response)
                if (isset($data['taxpayerInfo'])) {
                    $info = $data['taxpayerInfo'];

                    // Ensure we have the tradeNam and primary address data
                    if (isset($info['tradeNam'], $info['pradr']['addr'])) {
                        $addr = $info['pradr']['addr'];

                        // Build a formatted address string using available address parts
                        $fullAddress = implode(', ', array_filter([
                            $addr['flno']    ?? null,
                            $addr['bno']     ?? null,
                            $addr['bnm']     ?? null,
                            $addr['landMark']?? null,
                            $addr['loc']     ?? null,
                            $addr['st']      ?? null,
                        ]));

                        return response()->json([
                            'success' => true,
                            'message' => 'Valid GSTIN.',
                            'data' => [
                                'company_name' => $info['tradeNam'],
                                'name'         => $info['tradeNam'],
                                'address'      => $fullAddress,
                                'city'         => $addr['dst']  ?? null,
                                'state'        => $addr['stcd'] ?? null,
                                'pincode'      => $addr['pncd'] ?? null,
                            ]
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'GSTIN verified but primary address is missing.',
                            'data'    => $data
                        ], 200);
                    }
                } else {
                    // If taxpayerInfo is not set, assume it's an error response (e.g., invalid GSTIN)
                    return response()->json([
                        'success' => false,
                        'message' => $data['message'] ?? 'GSTIN verification failed.',
                        'data'    => $data
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid GSTIN or AppyFlow API error.',
                    'error'   => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while verifying GSTIN.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function fetchBanners()
    {
        return response()->json([
            'success' => true,
            'message' => 'Units fetched successfully!',
            'data' => [
                'https://api.stockoutindia.com/storage/uploads/banners/banner_1.jpg',
                'https://api.stockoutindia.com/storage/uploads/banners/banner_2.jpg'
            ]
            
        ], 200);
    }

    public function usersWithProducts(Request $request)
    {
        try {
            // Get limit and offset from the request
            $limit = $request->input('limit', 10); // Default limit is 10
            $offset = $request->input('offset', 0); // Default offset is 0

            // Build the query to fetch users with their active products, applying limit and offset
            $users = User::with(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->offset($offset)
            ->limit($limit)
            ->get();

            // Get total count of users (without pagination) to return the total count
            $totalCount = User::count();

            return response()->json([
                'code' => 200,
                'success' => true,
                'data' => $users,
                'total_count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function userOrders(Request $request)
    {
        try {
            // Get limit and offset from the request
            $limit = $request->input('limit', 10); // Default limit is 10
            $offset = $request->input('offset', 0); // Default offset is 0
            $userId = $request->input('user_id'); // User ID filter (optional)

            // Build the query to fetch orders, with pagination
            $query  = RazorpayOrdersModel::with(['user', 'get_product']);

            // If user_id is provided, filter by user_id
            if ($userId) {
                $query->where('user', $userId);
            }

            // Get total count without limit/offset
            $totalCount = $query->count();
            
            // Build the query to fetch orders, with pagination
            $orders = $query->offset($offset)->limit($limit)->get();

            $grouped = $orders->groupBy('get_user')->map(function ($orders) {
                return [
                    'user' => $orders->first()->user,
                    'orders' => $orders->map(function ($order) {
                        return [
                            'order_id' => $order->id,
                            'product' => [
                                'id' => $order->get_product->id,
                                'name' => $order->get_product->name,
                                // Add other product details as needed
                            ],
                            'amount' => $order->payment_amount,
                            'status' => $order->status,
                        ];
                    }),
                ];
            })->values();

            return response()->json([
                'code' => 200,
                'success' => true,
                'data' => $grouped,
                'total_count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function toggleUserStatus(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'is_active' => 'required|boolean',
            ]);

            $user = User::find($request->user_id);
            $user->is_active = $request->is_active;
            $user->save();

            return response()->json([
                'code' => 200,
                'success' => true,
                'message' => 'User status updated successfully.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
