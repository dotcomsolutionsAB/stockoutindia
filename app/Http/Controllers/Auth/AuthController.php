<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

class AuthController extends Controller
{
    //
    // user `login`
    public function login(Request $request, $otp = null)
    {
    if ($otp) 
    {
        $request->validate([
            'username' => 'required',
        ]);
        
        $otpRecord = User::select('otp', 'expires_at')
        ->where('username', $request->username)
        ->first();

        if($otpRecord)
        {
            if(!$otpRecord || $otpRecord->otp != $otp)
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, invalid OTP!'
                ], 401);
            }
            elseif ($otpRecord->expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, OTP has expired!'
                ], 400);
            }

            else {
                // Remove OTP record after successful validation
                User::select('otp')->where('username', $request->username)->update(['otp' => null, 'expires_at' => null]);

                // Retrieve the use
                $user = User::where('username', $request->username)->first();

                // Generate a sanctrum token
                $generated_token = $user->createToken('API TOKEN')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'User logged in successfully!',
                    'data' => [
                        'token' => $generated_token,
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                    ],
                ], 200);
            }
        }

        else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not register.',
                ], 404);
        }
    }
    
    else {
        $request->validate([
            'username' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8', // Minimum 8 characters
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[a-z]/', // At least one lowercase letter
                'regex:/[0-9]/', // At least one number
                'regex:/[@$!%*?&#]/', // At least one special character
            ],
        ]);

        if(Auth::attempt(['username' => $request->username, 'password' => $request->password]))
        {
            $user = Auth::user();

            if ($user->role == "user") {
                // Revoke previous tokens
                $user->tokens()->delete();
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
                'message' => 'User not register.',
            ], 404);
        }
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

    // genearate otp and send to `whatsapp`
    public function generate_otp(Request $request)
    {
        $request->validate([
            'username' => 'required',
        ]);

        $username = $request->input('username');
        
        $get_user = User::select('id', 'mobile')
            ->where('username', $username)
            ->first();
        
        if(!$get_user == null)
        {
            $mobile = $get_user->mobile;

            $six_digit_otp = random_int(100000, 999999);

            $expiresAt = now()->addMinutes(10);

            $store_otp = User::where('mobile', $mobile)
                            ->update([
                                'otp' => $six_digit_otp,
                                'expires_at' => $expiresAt,
                            ]);

            if($store_otp)
            {
                $templateParams = [
                    'name' => 'login_otp', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp,
                                ],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            "index" => "0",
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp,
                                ],
                            ],
                        ]
                    ],
                ];

                $whatsappUtility = new sendWhatsAppUtility();

                $response = $whatsappUtility->sendWhatsApp($mobile, $templateParams, $mobile, 'OTP Campaign');

                return response()->json([
                    'success' => true,
                    'message' => 'Otp sent successfully!'
                ], 200);
            }
        }
        else {
            return response()->json([
                'success' => true,
                'message' => 'User has not registered!',
            ], 404);
        }
    }
}
