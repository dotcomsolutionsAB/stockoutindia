<?php

namespace App\Http\Controllers\Auth;
use App\Services\GoogleAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Mail\SendNewPassword;
use App\Models\User;
use Auth;

class AuthController extends Controller
{
    //
    protected $googleAuth;

    public function __construct(GoogleAuthService $googleAuth)
    {
        $this->googleAuth = $googleAuth;
    }

    // user `login`
    public function login(Request $request)
    {
        try 
        {
            // Step 1: Check if logging in via Google
            if ($request->has('idToken')) {
                $request->validate([
                    'idToken' => 'required|string',
                ]);

                // Call the reusable function
                $payload = $this->googleAuth->verifyGoogleToken(
                    $request->idToken,
                    env('GOOGLE_CLIENT_ID')
                );

                if (!$payload) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired Google ID token.',
                    ], 401);
                }

                // Extract user info from payload
                $email = $payload['email'] ?? null;
                $googleId = $payload['sub'] ?? null;

                $user = User::where('email', $email)->first();

                if ($user) {
                    if ($user->is_active == 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Your account is inactive. Please contact support.',
                        ], 403);
                    }

                    if ($user->google_id !== $googleId) {
                        $user->google_id = $googleId;
                        $user->save();
                    }

                    $token = $user->createToken('API TOKEN')->plainTextToken;

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
                        ]
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'account_created' => false,
                        'message' => 'User not found. Proceed to registration.',
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

    // forget password
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        try {
            $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            // Fetch user by email or username
            $user = User::where($loginField, $request->username)->firstOrFail();

            // Generate strong password
            $newPassword = $this->generateStrongPassword();

            // Update DB
            $user->password = Hash::make($newPassword);
            $user->save();

            // Invalidate old tokens (if any)
            //$user->tokens()->delete();

            if($user->name == '') {
                $user->name = 'User';
            }

            // Send password to user's email
            Mail::to($user->email)->send(new SendNewPassword($user->name, $newPassword));

            return response()->json([
                'code' => 200,
                'success' => true,
                'message' => 'New password has been sent to your email address.',
                // 'password' => $newPassword,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    // helper function for generate a strong password
    private function generateStrongPassword($length = 12)
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '@$!%*?&#';
        $all = $upper . $lower . $numbers . $special;

        return substr(str_shuffle(
            str_shuffle($upper)[0] .
            str_shuffle($lower)[0] .
            str_shuffle($numbers)[0] .
            str_shuffle($special)[0] .
            str_shuffle($all)
        ), 0, $length);
    }

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
