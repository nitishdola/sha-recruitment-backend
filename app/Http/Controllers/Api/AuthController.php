<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}


    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'mobile'          => 'required|digits:10',
            'recaptcha_token' => 'required',
        ]);

        // Verify reCAPTCHA
        $result = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('recaptcha.secret_key'),
            'response' => $request->recaptcha_token,
            'remoteip' => $request->ip(),
        ])->json();

        if (!($result['success'] ?? false)) {
            return response()->json(['message' => 'Captcha verification failed.'], 422);
        }

        $otp = $this->otpService->send($request->mobile);

        if (!$otp) {
            return response()->json(['message' => 'Failed to send OTP. Please try again.'], 500);
        }

        OtpVerification::updateOrCreate(
            ['mobile'     => $request->mobile],
            ['otp'        => $otp,
             'expires_at' => Carbon::now()->addMinutes(10)]
        );

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'otp'    => 'required|digits:4',
        ]);

        if (!$this->otpService->verify($request->mobile, $request->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $user = User::firstOrCreate(['mobile' => $request->mobile]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }
}