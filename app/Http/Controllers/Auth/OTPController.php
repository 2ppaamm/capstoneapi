<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use App\Services\SmsService;
use App\User;

class OTPController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'contact' => 'required|string'
        ]);

        $contact = $request->contact;
        $otp = rand(100000, 999999);

        // Detect contact type
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'phone_number';

        // Create or find user
        $user = User::firstOrCreate(
            [$field => $contact],
            ['name' => 'User-' . Str::random(6)]
        );

        // Save OTP
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        // Send OTP via Email or SMS

        if ($isEmail) {
            Mail::to($contact)->send(new SendOtpMail($otp));
        } else {
            try {
                $smsService = new SmsService();
                $smsService->send($contact, "Your AllGifted OTP is: $otp");
            } catch (\Exception $e) {
                \Log::error("SMS sending failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'OTP sent',
            'otp_preview' => $otp, // 🔒 remove this in production
        ]);
    }

	public function verifyOtp(Request $request)
	{
	    $request->validate([
	        'contact' => 'required|string',
	        'otp_code' => 'required|string',
	    ]);

	    $contact = $request->contact;
	    $field = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

	    $user = User::where($field, $contact)
	        ->where('otp_code', $request->otp_code)
	        ->where('otp_expires_at', '>', now())
	        ->first();

	    if (!$user) {
	        return response()->json(['message' => 'Invalid or expired OTP'], 401);
	    }
	    
	    if (is_null($user->lives)) {
	        $user->lives = 5;
	    }
	    // Clear the OTP once verified
	    $user->otp_code = null;
	    $user->otp_expires_at = null;
	    $user->save();

	    $token = $user->createToken('login')->plainTextToken;

	    $isSubscriber = !is_null($user->date_of_birth) && !is_null($user->firstname);


	    return response()->json([
	        'message' => 'OTP verified. Login successful.',
	        'token' => $token,
	        'user_id' => $user->id,
	        'first_name' => $user->firstname,
	        'is_subscriber' => $isSubscriber,
	        'dob' => $user->date_of_birth,
	        'maxile_level' => (int) round($user->maxile_level),
	        'game_level' => (int) $user->game_level,
	        'lives' => (int) $user->lives,
	    ]);
	}


}
