<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use App\Services\AppleAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\SendNewPassword;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Auth;

class AuthController extends Controller
{
    //
    protected $googleAuth;
    protected $appleAuth;

    public function __construct(GoogleAuthService $googleAuth, AppleAuthService $appleAuth)
    {
        $this->googleAuth = $googleAuth;
        $this->appleAuth = $appleAuth;
    }


    // user `login`
    public function login(Request $request)
    {
        try 
        {
            // Step 1: Check if logging in via Google
            if ($request->has('idToken')) {
                Log::info('Google login attempt started.');

                $request->validate([
                    'idToken' => 'required|string',
                ]);
                Log::info('idToken validation passed.');

                // Call the reusable function
                // $payload = $this->googleAuth->verifyGoogleToken(
                //     $request->idToken,
                //     env('GOOGLE_CLIENT_ID')
                // );

                $clientId = config('services.google.client_id');

                if (empty($clientId)) {
                    Log::error('Google client ID is missing in config.');
                    return response()->json([
                        'success' => false,
                        'message' => 'Server misconfiguration: Google Sign-In is not configured.',
                    ], 500);
                }

                $payload = $this->googleAuth->verifyGoogleToken(
                    $request->idToken,
                    $clientId
                );


                if (!$payload) {
                    Log::warning('Invalid or expired Google ID token.');

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired Google ID token.',
                    ], 401);
                }

                Log::info('Google ID token verified successfully.');

                // Extract user info from payload
                $email = $payload['email'] ?? null;
                $googleId = $payload['sub'] ?? null;
                $name = $payload['name'] ?? 'User';
                Log::info('Extracted email: ' . $email . ', Google ID: ' . $googleId . ', Payload: ' . json_encode($payload));

                $user = User::where('email', $email)->first();

                if ($user) {
                    Log::info('User found: ' . $user->name);

                    if ($user->is_active == 0) {
                        Log::warning('User account is inactive: ' . $user->name);

                        return response()->json([
                            'success' => false,
                            'message' => 'Your account is inactive. Please contact support.',
                        ], 403);
                    }

                    if ($user->google_id !== $googleId) {
                        Log::info('Updating Google ID for user: ' . $user->name);
                        $user->google_id = $googleId;
                        $user->save();
                    }

                    $token = $user->createToken('API TOKEN')->plainTextToken;

                    Log::info('Google login successful for user: ' . $user->name);

                    return response()->json([
                        'success' => true,
                        'message' => 'Google login successful.',
                        'account_created' => true,
                        'data' => [
                            'token' => $token,
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                            'username' => $user->username,
                            'email' => $user->email,
                        ]
                    ], 200);
                } else {
                    Log::info('User not found with email: ' . $email);

                    return response()->json([
                        'success' => true,
                        'account_created' => false,
                        'message' => 'User not found. Proceed to registration.',
                        'data' => [
                            'name' => $name,
                            'email' => $email,
                        ]
                    ], 200);
                }

                Log::warning('No idToken provided in the request.');
                return response()->json([
                    'success' => false,
                    'message' => 'No idToken provided.',
                ], 400);

            }

            if ($request->has('appleIdToken')) {
                Log::info('Apple login attempt started.');
            
                $request->validate([
                    'appleIdToken' => 'required|string',
                ]);
            
                $payload = $this->appleAuth->verifyAppleToken(
                    $request->appleIdToken,
                    env('APPLE_CLIENT_ID') // Replace with your Apple Service ID
                );
            
                if (!$payload) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired Apple ID token.',
                    ], 401);
                }
            
                $email = $payload['email'] ?? null; // Might not always be present
                $appleSub = $payload['sub'] ?? null; // Unique Apple user ID
                $name = $payload['name'] ?? null; // Unique Apple user ID
                Log::info('Extracted email: ' . $email . ', Google ID: ' . $appleSub . ', Payload: ' . json_encode($payload));
            
                $user = User::where('apple_id', $appleSub)->first();
            
                if (!$user && $email) {
                    // Try finding by email (if user used Apple with email)
                    $user = User::where('email', $email)->first();
                }
            
                if ($user) {
                    if ($user->is_active == 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Your account is inactive. Please contact support.',
                        ], 403);
                    }
            
                    if ($user->apple_id !== $appleSub) {
                        $user->apple_id = $appleSub;
                        $user->save();
                    }
            
                    $token = $user->createToken('API TOKEN')->plainTextToken;
            
                    return response()->json([
                        'success' => true,
                        'message' => 'Apple login successful.',
                        'account_created' => true,
                        'data' => [
                            'token' => $token,
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                            'username' => $user->username,
                            'email' => $user->email,
                        ]
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'account_created' => false,
                        'message' => 'User not found. Proceed to registration.',
                        'data' => [
                            'name' => $name,
                            'email' => $email,
                        ]
                    ], 200);
                }
            }
            

            
            // Step 2: Fallback to standard username/password login

            $request->validate([
                'username' => 'required|string',
                'password' => [
                    'required',
                    'string',
                ],
            ]);

            $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            if(Auth::attempt([$loginField => $request->username, 'password' => $request->password]))
            {
                $user = Auth::user();

                // Check if the user is inactive
                if ($user->is_active == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account is inactive. Please contact support.',
                    ], 403);
                }

                if ($user->role == "user") {
                    // Revoke previous tokens
                    //$user->tokens()->delete();
                }
                // Generate a sanctrum token
                $generated_token = $user->createToken('API TOKEN')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $generated_token,
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'username' => $user->username,
                    ],
                    'message' => 'User logged in successfully!',
                ], 200);
            }

            else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Username or Password.',
                ], 200);
            }
        } 
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
        
    }

    // Admin login as user 
    public function loginAsUser(Request $request)
    {
        try {
            // 1) Validate input
            $request->validate([
                'user_id'        => 'required|integer|exists:users,id',
                'super_password' => 'required|string',
            ]);

            // 2) (Optional but recommended) Make sure caller is an admin
            //    Only if route is protected by auth:sanctum
            // $admin = $request->user();
            // if (!$admin || $admin->role !== 'admin') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Only admins can use this endpoint.',
            //     ], 403);
            // }

            // 3) Verify super password
            $providedSuperPassword = $request->input('super_password');
            $configuredSuperPassword = env('SUPER_LOGIN_PASSWORD');

            if (empty($configuredSuperPassword)) {
                Log::error('SUPER_LOGIN_PASSWORD is not set in .env');
                return response()->json([
                    'success' => false,
                    'message' => 'Server misconfiguration. Super login password not set.',
                ], 500);
            }

            // Plain text check (simple)
            if ($providedSuperPassword !== $configuredSuperPassword) {
                Log::warning('Invalid super password attempt for login-as-user');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 403);
            }

            // If using hashed version instead:
            // if (!Hash::check($providedSuperPassword, $configuredSuperPassword)) { ... }

            // 4) Fetch the target user
            $user = User::find($request->user_id);

            if (!$user) {
                // Should not happen because of 'exists' rule, but just in case
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // 5) Check if user is active (same as normal login)
            if ($user->is_active == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user account is inactive. Please contact support.',
                ], 403);
            }

            // 6) Optionally revoke previous tokens for that user (if you want a clean session)
            // if ($user->role == "user") {
            //     $user->tokens()->delete();
            // }

            // 7) Create a token as if the user logged in normally
            $generated_token = $user->createToken('API TOKEN')->plainTextToken;

            // 8) Return SAME RESPONSE SHAPE as normal username/password login
            return response()->json([
                'success' => true,
                'data' => [
                    'token'    => $generated_token,
                    'user_id'  => $user->id,
                    'name'     => $user->name,
                    'role'     => $user->role,
                    'username' => $user->username,
                    'email'    => $user->email ?? null, // optional, to match others
                ],
                'message' => 'Logged in as user successfully (admin override).',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in loginAsUser: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // user `logout`
    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if(!$request->user()) {
            return response()->json([
                'success'=> false,
                'message'=>'Sorry, no user is logged in now!',
            ], 401);
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully!',
        ], 204);
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->username)->first();
            $newPassword = $this->generateRandomPassword(12);

            // 1) Save WITHOUT keeping a long transaction open
            DB::beginTransaction();
            $user->password = Hash::make($newPassword);
            if (empty($user->name)) {
                $user->name = 'User';
            }
            $user->save();
            DB::commit(); // ✅ commit BEFORE SMTP

            // 2) Send email AFTER commit (no DB lock/transaction)
            try {
                // Option A: immediate send after commit
                // Mail::to($user->email)->send(new SendNewPassword($user, $newPassword));

                // Option B (better): send after commit & use queue if available
                Mail::to($user->email)
                    ->send((new SendNewPassword($user, $newPassword))->afterCommit());

            } catch (\Exception $mailEx) {
                // Email failure shouldn’t undo the password change.
                return response()->json([
                    'code'    => 200,
                    'success' => true,
                    'message' => 'Password updated, but failed to send the email. Use the password in the response and change it after login.',
                    'data'    => ['password' => $newPassword],
                    'error'   => $mailEx->getMessage(), // hide in prod if you want
                ], 200);
            }

            return response()->json([
                'code'    => 200,
                'success' => true,
                'message' => 'A new password has been sent to your email address.',
                'data'    => ['password' => $newPassword],
            ], 200);

        } catch (ValidationException $ve) {
            return response()->json([
                'code'    => 422,
                'success' => false,
                'message' => 'Validation failed.',
                'data'    => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Ensure we’re not stuck mid-transaction
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'code'    => 500,
                'success' => false,
                'message' => 'Something went wrong.',
                'data'    => [],
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a cryptographically secure random password that includes:
     * - at least 1 lowercase
     * - at least 1 uppercase
     * - at least 1 digit
     * - at least 1 special char
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $length = max(8, $length); // enforce a sensible minimum

        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits  = '0123456789';
        $special = '!@#$%^&*()_-=+[]{}<>?';
        $all     = $lower . $upper . $digits . $special;

        // Guarantee at least one of each required class
        $password = [];
        $password[] = $lower[random_int(0, strlen($lower) - 1)];
        $password[] = $upper[random_int(0, strlen($upper) - 1)];
        $password[] = $digits[random_int(0, strlen($digits) - 1)];
        $password[] = $special[random_int(0, strlen($special) - 1)];

        // Fill the remainder with secure random chars
        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Securely shuffle the result
        // (Fisher–Yates shuffle using random_int)
        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }


    // forget password
    // public function forgotPassword(Request $request)
    // {
    //     $request->validate([
    //         'username' => 'required|string',
    //     ]);

    //     try {
    //         $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    //         // Fetch user by email or username
    //         $user = User::where($loginField, $request->username)->firstOrFail();

    //         // Generate strong password
    //         $newPassword = $this->generateStrongPassword();

    //         // Update DB
    //         $user->password = Hash::make($newPassword);
    //         $user->save();

    //         // Invalidate old tokens (if any)
    //         //$user->tokens()->delete();

    //         if($user->name == '') {
    //             $user->name = 'User';
    //         }

    //         // Send password to user's email
    //         Mail::to($user->email)->send(new SendNewPassword($user->name, $newPassword));

    //         return response()->json([
    //             'code' => 200,
    //             'success' => true,
    //             'message' => 'New password has been sent to your email address.',
    //             'password' => $newPassword,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'code' => 500,
    //             'success' => false,
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }
    // helper function for generate a strong password
    // private function generateStrongPassword($length = 12)
    // {
    //     $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    //     $lower = 'abcdefghijklmnopqrstuvwxyz';
    //     $numbers = '0123456789';
    //     $special = '@$!%*?&#';
    //     $all = $upper . $lower . $numbers . $special;

    //     return substr(str_shuffle(
    //         str_shuffle($upper)[0] .
    //         str_shuffle($lower)[0] .
    //         str_shuffle($numbers)[0] .
    //         str_shuffle($special)[0] .
    //         str_shuffle($all)
    //     ), 0, $length);
    // }

    // Reset Password Function
    public function resetPassword(Request $request)
    {
        // Validate the input fields
        $request->validate([
            'password' => 'required|string|confirmed', // Ensure password and password_confirmation fields
        ]);

        try {
            // Retrieve the currently authenticated user from the Bearer token
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'code' => 200,
                    'success' => false,
                    'message' => 'Unauthorized, please provide a valid token.',
                ], 200);
            }

            // Update the user's password
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'code' => 200,
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
