<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle user registration
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (config('fortify.features.emailVerification')) {
            event(new Registered($user));
        }

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Handle user login
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        $deviceInfo = $this->getDeviceInfo($request);

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'device_info' => $deviceInfo,
        ], 201);
    }

    /**
     * Handle user logout
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccesstoken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Send a password reset link to the given user.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)]
        ]);
    }

    /**
     * Reset the given user's password.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)]
        ]);
    }

    /**
     * Helper method to extract device information from the request.
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getDeviceInfo(Request $request): array
    {
        $userAgent = $request->header('User-Agent');
        $ipAddress = $request->ip();
        $acceptLanguage = $request->header('Accept-Language');
        $platform = null;
        $browser = null;
        $deviceType = 'Unknown';

        if ($userAgent) {
            if (preg_match('/(msie|firefox|chrome|safari|opera|edge|trident)/i', $userAgent, $matches)) {
                $browser = strtolower($matches[1]);

                if ($browser === 'msie' || $browser === 'trident') $browser = 'Internet Explorer';
            }

            if (preg_match('/windows|linux|mac os|android|iphone|ipad/i', $userAgent, $matches)) {
                $platform = strtolower($matches[0]);
            }

            if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
                $deviceType = 'Mobile';
            } elseif (preg_match('/tablet/i', $userAgent)) {
                $deviceType = 'Tablet';
            } else {
                $deviceType = 'Desktop';
            }
        }

        return [
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'accept_language' => $acceptLanguage,
            'browser' => $browser,
            'platform' => $platform,
            'device_type' => $deviceType,
        ];
    }
}
