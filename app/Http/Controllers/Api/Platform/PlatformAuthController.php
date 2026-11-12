<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

class PlatformAuthController extends Controller
{   
    /**
     * Authentication platform user
     * 
     * @param Request $request, platform user request
     * @return JsonResponse 200, successfully authenticated 
     */
    public function login(Request $request): JsonResponse 
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = PlatformUser::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('platform-token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Register a new user in the platform
     * 
     * @param Request $request
     * @return JsonResponse 201, user successfully created
     */
    public function register(Request $request): JsonResponse 
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|string|max:191|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = PlatformUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (config('fortify.features.emailVerification'))
        {
            event(new Registered($user));
        }

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * Logout the user
     * 
     * @param Request $request, user request logout
     * @return JsonResponse 200, user sucessfully logout
     */
    public function logout(Request $request): JsonResponse 
    {
        $request->user()?->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Check the user info
     * 
     * @param Request $request, user request
     * @return JsonResponse 200, user info returned successfully
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * Feed all necessary information the user
     * 
     * @param  PlatformUser $user, user whow made the request
     * @return array List with all user attributes 
     */
    private function userPayload(PlatformUser $user): array 
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]; 
    }
}
