<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private string $authKey;
    private string $templateId;
    private string $senderId;

    public function __construct()
    {
        $this->authKey    = config('services.msg91.auth_key');
        $this->templateId = config('services.msg91.template_id');
        $this->senderId   = config('services.msg91.sender_id');
    }

    /**
     * Send OTP via MSG91. Returns the OTP string on success, false on failure.
     */
    public function send(string $mobile): string|false
    {
        $otp = $this->generateOtp();
        Cache::put("otp:{$mobile}", $otp, now()->addMinutes(10));

        $response = Http::withHeaders([
            'authkey'      => $this->authKey,
            'Content-Type' => 'application/json',
            'accept'       => 'application/json',
        ])->post('https://control.msg91.com/api/v5/flow', [
            'template_id' => $this->templateId,
            'short_url'   => '0',
            'recipients'  => [
                [
                    'mobiles' => '91' . $mobile,
                    'otp'     => $otp,
                ]
            ],
        ]);

        Log::info('MSG91 response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if (!$response->successful()) {
            Log::error('MSG91 send failed', ['body' => $response->body()]);
            Cache::forget("otp:{$mobile}");
            return false;
        }

        $result = $response->json();
        if (($result['type'] ?? '') !== 'success') {
            Log::error('MSG91 returned non-success', ['result' => $result]);
            Cache::forget("otp:{$mobile}");
            return false;
        }

        return $otp;
    }

    /**
     * Verify OTP from cache. Deletes it on success to prevent reuse.
     */
    public function verify(string $mobile, string $otp): bool
    {
        $cached = Cache::get("otp:{$mobile}");

        if (!$cached || (string) $cached !== (string) $otp) {
            return false;
        }

        Cache::forget("otp:{$mobile}");
        return true;
    }

    private function generateOtp(): string
    {
        return (string) random_int(1000, 9999);
    }
}