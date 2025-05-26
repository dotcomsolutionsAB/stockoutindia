<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RazorpayOrdersModel;
use App\Models\Users;
use App\Models\ProductModel;
use App\Models\UploadModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\GoogleAuthService;
use App\Services\AppleAuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $googleAuth;
    protected $appleAuth;

    public function __construct(GoogleAuthService $googleAuth, AppleAuthService $appleAuth)
    {
        $this->googleAuth = $googleAuth;
        $this->appleAuth = $appleAuth;
    }
    // create
    // public function register(Request $request)
    // {
    //     try {

    //         $google_id_token = $request->input('idToken'); // idToken from Google

    //         $googleEmail = null;
    //         $googleId = null;
    
    //         // Step 1: If idToken is provided, verify it
    //         if ($google_id_token) {
    //             $payload = $this->googleAuth->verifyGoogleToken(
    //                 $google_id_token,
    //                 env('GOOGLE_CLIENT_ID')
    //             );
    
    //             $googleEmail = $payload['email'] ?? null;
    //             $googleId = $payload['sub'] ?? null;
    //         }

    //         // Step 2: Dynamically set validation rules
    //         $rules = [
    //             // 'email' => ['required', 'email', 'unique:users,email'],
    //             'role' => ['required', Rule::in(['admin', 'user'])],
    //             'phone' => 'required|string|unique:users,phone',
    //             'gstin' => 'nullable|string|unique:users,gstin',
    //             'name' => 'required_without:gstin|string|max:255',
    //             'company_name' => 'required_without:gstin|string|max:255',
    //             'address' => 'required_without:gstin|string|max:255',
    //             'pincode' => 'required_without:gstin|string|max:10',
    //             'city' => 'required_without:gstin|string',
    //             'state' => 'required_without:gstin|integer|exists:t_states,id',
    //             'industry' => 'nullable|integer|exists:t_industries,id',
    //             'sub_industry' => 'nullable|integer|exists:t_sub_industries,id',
    //         ];

    //         // If googleId is not available, validate email
    //         if (!$googleId) {
    //             $rules['email'] = ['required', 'email', 'unique:users,email'];
    //         }

    //         // Validate the data
    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $validator->errors()->first(),
    //             ], 200);
    //         }

    //         // If googleEmail exists, ensure it is unique
    //         if ($googleEmail && User::where('email', $googleEmail)->exists()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'The email associated with the Google account is already in use.',
    //             ], 200);
    //         }

    //         // Create User
    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $googleEmail ?? $request->email,
    //             'password' => bcrypt($request->password), // No password if using google login
    //             'google_id' => $googleId,
    //             'role' => $request->role,
    //             'username' => $request->phone, // Store phone in username
    //             'phone' => $request->phone,
    //             'is_active' => "1",
    //             'company_name' => $request->company_name,
    //             'address' => $request->address,
    //             'pincode' => $request->pincode,
    //             'city' => $request->city,
    //             'state' => $request->state,
    //             'gstin' => $request->gstin,
    //             'industry' => $request->industry,
    //             'sub_industry' => $request->sub_industry,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User registered successfully!',
    //             'data' => $user->makeHidden(['id', 'created_at', 'updated_at']),
    //         ], 201);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function register(Request $request)
    {
        try {
            $google_id_token = $request->input('idToken'); // idToken from Google
            $googleEmail = null;
            $googleId = null;

            // Step 1: If idToken is provided, verify it
            if ($google_id_token) {
                $payload = $this->googleAuth->verifyGoogleToken(
                    $google_id_token,
                    env('GOOGLE_CLIENT_ID')
                );

                if (!$payload) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired Google ID token.',
                    ], 401);
                }

                $googleEmail = $payload['email'] ?? null;
                $googleId = $payload['sub'] ?? null;
            }

            $apple_id_token = $request->input('appleIdToken'); // idToken from Google
            $appleEmail = null;
            $appleId = null;

            // Step 1: If idToken is provided, verify it
            if ($apple_id_token) {
                $payload = $this->appleAuth->verifyAppleToken(
                    $apple_id_token,
                    env('APPLE_CLIENT_ID')
                );

                if (!$payload) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired Google ID token.',
                    ], 401);
                }

                $appleEmail = $payload['email'] ?? null;
                $appleId = $payload['sub'] ?? null;
            }

            \Log::info('Register using Apple : ', [
                    'apple_id_token' => $apple_id_token,
                    'appleEmail' => $appleEmail,
                    'appleId' => $appleId,
                    'request' => json_encode($request->all()),
                ]);

            // Step 2: Dynamically set validation rules
            $rules = [
                'role' => ['required', Rule::in(['admin', 'user'])],
                'phone' => 'required|string|unique:users,phone',
                'gstin' => 'nullable|string|unique:users,gstin',
                'name' => 'required_without:gstin|string|max:255',
                'company_name' => 'required_without:gstin|string|max:255',
                'address' => 'required_without:gstin|string|max:255',
                'pincode' => 'required_without:gstin|string|max:10',
                'city' => 'required_without:gstin|string',
                'state' => 'required_without:gstin|integer|exists:t_states,id',
                'industry' => 'nullable|integer|exists:t_industries,id',
                'sub_industry' => 'nullable|integer|exists:t_sub_industries,id',
            ];

            if (!$googleId && !$appleId) {
                $rules['email'] = ['required', 'email', 'unique:users,email'];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            if ($googleEmail && User::where('email', $googleEmail)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The email associated with the Google account is already in use.',
                ], 200);
            }

            if ($appleEmail && User::where('email', $appleEmail)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The email associated with the Apple account is already in use.',
                ], 200);
            }

            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $googleEmail ?? $request->email,
                'password' => $request->password ? bcrypt($request->password) : bcrypt(Str::random(16)),
                'google_id' => $googleId,
                'apple_id' => $appleId,
                'role' => $request->role,
                'username' => $request->phone,
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

            // Send signup confirmation email
            try {
                Mail::to($user->email)->send(new SignupConfirmationMail($user->name));
            } catch (\Exception $mailEx) {
                // Log the error, but do not fail the registration for email issues
                \Log::error('Failed to send signup confirmation email: '.$mailEx->getMessage());
            }

            // If registered via Google, generate and return the token
            if ($googleId) {
                $token = $user->createToken('API TOKEN')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'User registered successfully via Google!',
                    'account_created' => true,
                    'data' => [
                        'token' => $token,
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'username' => $user->username,
                        'email' => $user->email,
                    ]
                ], 201);
            }

            // If registered via Apple, generate and return the token
            if ($appleId) {
                $token = $user->createToken('API TOKEN')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'User registered successfully via Apple!',
                    'account_created' => true,
                    'data' => [
                        'token' => $token,
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'username' => $user->username,
                        'email' => $user->email,
                    ]
                ], 201);
            }

            // Standard response for non-Google registrations
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

    // public function fetchBanners()
    // {
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Units fetched successfully!',
    //         'data' => [
    //             'https://api.stockoutindia.com/storage/uploads/banners/banner_1.jpg',
    //             'https://api.stockoutindia.com/storage/uploads/banners/banner_2.jpg'
    //         ]
            
    //     ], 200);
    // }

    public function uploadBanner(Request $request)
    {
        try {
            if (!$request->hasFile('banners')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No banner files provided.',
                ], 400);
            }

            $files = $request->file('banners');

            // Delete old banners from DB and server
            $oldBanners = UploadModel::where('file_url', 'like', '%uploads/banners/%')->get();
            foreach ($oldBanners as $file) {
                $filePath = public_path('storage/uploads/banners/' . $file->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $file->delete();
            }

            $uploadedUrls = [];
            foreach ($files as $index => $file) {
                $originalExt = $file->getClientOriginalExtension();
                $newFileName = 'banner_file' . ($index + 1) . '.' . $originalExt;
                $relativePath = 'uploads/banners/' . $newFileName;

                $fileSize = $file->getSize(); // Get size before move

                // Move the file to storage
                $file->move(public_path('storage/uploads/banners'), $newFileName);

                // Save to DB
                UploadModel::create([
                    'file_name' => $newFileName,
                    'file_ext' => $originalExt,
                    'file_url' => $relativePath,
                    'file_size' => $fileSize, // Use the captured size
                ]);

                $uploadedUrls[] = url('storage/' . $relativePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banners uploaded successfully.',
                'data' => $uploadedUrls,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchBanners()
    {
        $banners = UploadModel::where('file_name', 'LIKE', 'banner_file%')
            ->orderBy('file_name')
            ->pluck('file_url')
            ->map(function ($url) {
                return url('storage/' . $url);
            });

        return response()->json([
            'success' => true,
            'message' => 'Banners fetched successfully!',
            'data' => $banners,
        ], 200);
    }

    public function usersWithProducts(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);
            $userIds = $request->input('user_ids');

            $query = User::with([
                'products' => function ($q) {
                    $q->where('status', 'active')
                        ->with(['industryDetails:id,name', 'subIndustryDetails:id,name']);
                },
                'industryDetails:id,name',
                'subIndustryDetails:id,name',
            ]);

            if ($userIds) {
                $userIdsArray = explode(',', $userIds);
                $query->whereIn('id', $userIdsArray);
            }

            $users = $query->offset($offset)->limit($limit)->get();
            $totalCount = User::count();

            // Fetch all upload records in one go
            $allUploads = UploadModel::pluck('file_url', 'id')->toArray();

            $data = $users->map(function ($user) use ($allUploads) {
                $products = $user->products->map(function ($product) use ($allUploads) {
                    $imageUrls = [];
                    if (!empty($product->image)) {
                        $imageIds = explode(',', $product->image);
                        foreach ($imageIds as $id) {
                            if (isset($allUploads[$id])) {
                                $imageUrls[] = url($allUploads[$id]);
                            }
                        }
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'selling_price' => $product->selling_price,
                        'image_urls' => $imageUrls,
                        'industry' => [
                            'id' => $product->industryDetails->id ?? null,
                            'name' => $product->industryDetails->name ?? null,
                        ],
                        'sub_industry' => [
                            'id' => $product->subIndustryDetails->id ?? null,
                            'name' => $product->subIndustryDetails->name ?? null,
                        ],
                    ];
                });

                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'role' => $user->role,
                    'username' => $user->username,
                    'phone' => $user->phone,
                    'status' => $user->is_active ? 'active' : 'in-active',
                    'company_name' => $user->company_name,
                    'address' => $user->address,
                    'pincode' => $user->pincode,
                    'city' => $user->city,
                    'state' => $user->state,
                    'gstin' => $user->gstin,
                    'industry' => [
                        'id' => $user->industryDetails->id ?? null,
                        'name' => $user->industryDetails->name ?? null,
                    ],
                    'sub_industry' => [
                        'id' => $user->subIndustryDetails->id ?? null,
                        'name' => $user->subIndustryDetails->name ?? null,
                    ],
                    'active_product_count' => $products->count(),
                    'products' => $products,
                ];
            });

            return response()->json([
                'code' => 200,
                'success' => true,
                'data' => $data,
                'total_count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function userOrders(Request $request)
    {
        try {
            // Get limit and offset from the request
            $limit = $request->input('limit', 10); // Default limit is 10
            $offset = $request->input('offset', 0); // Default offset is 0
            $userId = $request->input('user_id'); // User ID filter (optional)

            // Build the query to fetch orders
            $query = RazorpayOrdersModel::query();

            // If user_id is provided, filter by user_id
            if ($userId) {
                $query->where('user', $userId);
            }

            // Get total count without limit/offset
            $totalCount = $query->count();

            // Get the orders with pagination
            $orders = $query->offset($offset)->limit($limit)->get();

            // Manually fetch related user and product data
            $grouped = $orders->map(function ($order) {
                // Manually fetching user and product
                $user = User::find($order->user);  // Find user by ID
                $product = ProductModel::find($order->product);  // Find product by ID

                return [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,  // Add other user details as needed
                    ] : null,  // If user is null, return null
                    'orders' => [
                        'order_id' => $order->id,
                        'razorpay_order_id' => $order->razorpay_order_id,
                        'amount' => $order->payment_amount,
                        'status' => $order->status,
                        'date' => $order->date,
                        'product' => $product ? [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->selling_price,  // Add other product details as needed
                        ] : null,  // If product is null, return null
                    ],
                ];
            });

            return response()->json([
                'code' => 200,
                'success' => true,
                'data' => $grouped,
                'total_count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
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
