<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tokens = $this->generateTokenPair($user, $request);

        return $this->responseWithTokens($tokens);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        $tokens = $this->generateTokenPair($user, $request);

        return $this->responseWithTokens($tokens);
    }

    /**
     * Get OAuth provider URL
     */
    public function getOAuthUrl(string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'github'])) {
            return response()->json(['error' => 'Unsupported provider'], 400);
        }

        try {
            $url = Socialite::driver($provider)
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json(['redirect_url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate OAuth URL'], 500);
        }
    }

    /**
     * Handle OAuth callback
     */
    public function handleOAuthCallback(string $provider, Request $request): JsonResponse
    {
        if (!in_array($provider, ['google', 'github'])) {
            return response()->json(['error' => 'Unsupported provider'], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                ['email' => $socialUser->getEmail()]
            );

            $tokens = $this->generateTokenPair($user, $request);

            return $this->responseWithTokens($tokens);
        } catch (\Exception $e) {
            return response()->json(['error' => 'OAuth authentication failed'], 400);
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token not provided'], 401);
        }

        try {
            // Decode the refresh token to get the jti
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            $jti = $payload->get('jti');

            // Find the refresh token in database
            $tokenRecord = RefreshToken::where('jti', $jti)->first();

            if (!$tokenRecord || !$tokenRecord->isValid()) {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            // Revoke the old refresh token
            $tokenRecord->update(['revoked' => true]);

            // Generate new token pair
            $tokens = $this->generateTokenPair($tokenRecord->user, $request);

            return $this->responseWithTokens($tokens);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        if ($refreshToken) {
            try {
                $payload = JWTAuth::setToken($refreshToken)->getPayload();
                $jti = $payload->get('jti');

                // Revoke the refresh token
                RefreshToken::where('jti', $jti)->update(['revoked' => true]);
            } catch (\Exception $e) {
                // Token might be invalid, but we still want to clear the cookie
            }
        }

        return response()->json(['message' => 'Successfully logged out'])
            ->cookie('refresh_token', '', -1); // Clear the cookie
    }

    /**
     * Generate access and refresh token pair
     */
    private function generateTokenPair(User $user, Request $request): array
    {
        // Generate access token (15 minutes)
        $accessToken = JWTAuth::customClaims([
            'exp' => now()->addMinutes(15)->timestamp,
            'type' => 'access'
        ])->fromUser($user);

        // Generate refresh token (7 days)
        $jti = Str::uuid();
        $refreshToken = JWTAuth::customClaims([
            'jti' => $jti,
            'exp' => now()->addDays(7)->timestamp,
            'type' => 'refresh'
        ])->fromUser($user);

        // Store refresh token in database
        RefreshToken::create([
            'jti' => $jti,
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7),
            'created_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return [
            'access' => $accessToken,
            'refresh' => $refreshToken,
        ];
    }

    /**
     * Create a JSON response with tokens and set refresh token cookie
     */
    private function responseWithTokens(array $tokens): JsonResponse
    {
        return response()->json([
            'access_token' => $tokens['access'],
        ])->cookie(
            'refresh_token',
            $tokens['refresh'],
            60 * 24 * 7, // 7 days in minutes
            '/',          // path
            null,         // domain
            true,         // secure
            true,         // httpOnly
            false,        // raw
            'Lax'         // sameSite
        );
    }
}
