<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for API key in headers
        $apiKey = $request->header('X-API-Key');

        if ($apiKey) {
            return $this->authenticateWithApiKey($request, $next, $apiKey);
        }

        // Check for Bearer token
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            return $this->authenticateWithBearerToken($request, $next, $bearerToken);
        }

        // Fallback to standard authentication
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Authentication required',
                'error' => 'UNAUTHORIZED'
            ], 401);
        }

        return $next($request);
    }

    /**
     * Authenticate using API key
     */
    private function authenticateWithApiKey(Request $request, Closure $next, string $apiKey): Response
    {
        // Find user by API key
        $user = \App\Models\User::where('api_key', $apiKey)
            ->where('api_key_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired API key',
                'error' => 'INVALID_API_KEY'
            ], 401);
        }

        // Set authenticated user
        Auth::setUser($user);

        // Update last API usage
        $user->update(['api_last_used_at' => now()]);

        return $next($request);
    }

    /**
     * Authenticate using Bearer token
     */
    private function authenticateWithBearerToken(Request $request, Closure $next, string $token): Response
    {
        // Use Sanctum for Bearer token authentication
        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

        if (!$user) {
            return response()->json([
                'message' => 'Invalid bearer token',
                'error' => 'INVALID_BEARER_TOKEN'
            ], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}