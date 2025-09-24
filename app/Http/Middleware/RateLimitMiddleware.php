<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $type);

        if (RateLimiter::tooManyAttempts($key, $this->getMaxAttempts($type))) {
            return $this->buildTooManyAttemptsResponse($request, $key);
        }

        RateLimiter::hit($key, $this->getDecayMinutes($type) * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $this->getMaxAttempts($type),
            $this->calculateRemainingAttempts($key, $this->getMaxAttempts($type))
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();

        return hash('sha256', implode('|', [
            $type,
            $user ? $user->id : $request->ip(),
            $request->userAgent() ?? '',
        ]));
    }

    /**
     * Get the maximum number of attempts allowed.
     */
    protected function getMaxAttempts(string $type): int
    {
        return match ($type) {
            'login' => 5,
            'upload' => 10,
            'analysis' => 20,
            'api' => 100,
            'default' => 60,
        };
    }

    /**
     * Get the decay time in minutes.
     */
    protected function getDecayMinutes(string $type): int
    {
        return match ($type) {
            'login' => 15,
            'upload' => 60,
            'analysis' => 60,
            'api' => 60,
            'default' => 1,
        };
    }

    /**
     * Build the "too many attempts" response.
     */
    protected function buildTooManyAttemptsResponse(Request $request, string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        return redirect()->back()
            ->withErrors(['throttle' => "Too many attempts. Please try again in {$retryAfter} seconds."])
            ->withInput($request->except('password'));
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Calculate remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - RateLimiter::attempts($key));
    }
}