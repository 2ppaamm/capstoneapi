<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\User;

class OTPController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $otp = rand(100000, 999999);

        $user = User::firstOrCreate(
            ['phone_number' => $request->phone_number],
            ['name' => 'User-' . Str::random(6)]
        );

        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        return response()->json([
            'message' => 'OTP sent',
            'otp_preview' => $otp, // remove in production
        ]);
    }

	 public function verifyOtp(Request $request)
	{
	    $request->validate([
	        'phone_number' => 'required|string',
	        'otp_code' => 'required|string',
	    ]);

	    $user = User::where('phone_number', $request->phone_number)
	        ->where('otp_code', $request->otp_code)
	        ->where('otp_expires_at', '>', now())
	        ->first();

	    if (!$user) {
	        return response()->json(['message' => 'Invalid or expired OTP'], 401);
	    }

	    // Clear OTP after verification
	    $user->otp_code = null;
	    $user->otp_expires_at = null;
	    $user->save();

	    // Generate API token using Sanctum
	    $token = $user->createToken('login')->plainTextToken;

	    return response()->json([
	        'message' => 'OTP verified. Login successful.',
	        'token' => $token,
	        'user_id' => $user->id,
	    ]);
	}


}
