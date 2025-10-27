<?php

namespace App\Http\Controllers\Auth;
use App\Services\GoogleAuthService;
use App\Services\AppleAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Mail\SendNewPassword;
use App\Models\User;
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
                $payload = $this->googleAuth->verifyGoogleToken(
                    $request->idToken,
                    env('GOOGLE_CLIENT_ID')
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
            // Validate request
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Fetch the user
            $user = User::where('email', $request->email)->first();

            // Generate a strong password
            $newPassword = $this->generateRandomPassword(12);

            // Update password and send email atomically
            DB::beginTransaction();

            $user->password = Hash::make($newPassword);

            // Set a fallback name for the email template, if needed
            if (empty($user->name)) {
                $user->name = 'User';
            }

            $user->save();

            // Optionally invalidate old tokens (e.g., Sanctum)
            // if (method_exists($user, 'tokens')) {
            //     $user->tokens()->delete();
            // }

            try {
                Mail::to($user->email)->send(new SendNewPassword($user, $newPassword));
            } catch (\Exception $mailEx) {
                DB::rollBack();

                return response()->json([
                    'code'    => 500,
                    'success' => false,
                    'message' => 'Failed to send the email. Please try again later.',
                    'data'    => [],
                    'error'   => $mailEx->getMessage(), // remove in production if you prefer
                ], 500);
            }

            DB::commit();

            return response()->json([
                'code'    => 200,
                'success' => true,
                'message' => 'A new password has been sent to your email address.',
                // If you don’t want to expose the password in API response, return 'data' => []
                'data'    => ['password' => $newPassword],
            ], 200);
        } catch (ValidationException $ve) {
            // Uniform validation error shape
            return response()->json([
                'code'    => 422,
                'success' => false,
                'message' => 'Validation failed.',
                'data'    => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 500,
                'success' => false,
                'message' => 'Something went wrong.',
                'data'    => [],
                'error'   => $e->getMessage(), // remove in production if you prefer
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
