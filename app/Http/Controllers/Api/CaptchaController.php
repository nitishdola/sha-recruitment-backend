<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CaptchaController extends Controller
{
    public function generateCaptcha()
    {

        $key = "captcha_" . uniqid();

        $captcha = captcha_img('math');

        Cache::put($key, session('captcha'), now()->addMinutes(5));

        return response()->json([
            'key' => $key,
            'image' => $captcha
        ]);
    }
}