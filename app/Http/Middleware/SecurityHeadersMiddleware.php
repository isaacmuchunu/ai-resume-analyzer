<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Security headers to add to responses
     */
    private const SECURITY_HEADERS = [
        // Prevent XSS attacks
        'X-XSS-Protection' => '1; mode=block',

        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',

        // Prevent clickjacking
        'X-Frame-Options' => 'DENY',

        // Referrer policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Permissions policy
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isLocal = app()->environment(['local', 'testing']);

        // Add basic security headers
        foreach (self::SECURITY_HEADERS as $header => $value) {
            $response->headers->set($header, $value, false);
        }

        // Add HSTS only in production (not local development)
        if (!$isLocal) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Add CSP header
        $csp = $this->buildContentSecurityPolicy($request);
        $response->headers->set('Content-Security-Policy', $csp, false);

        return $response;
    }

    /**
     * Build Content Security Policy
     */
    private function buildContentSecurityPolicy(Request $request): string
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp-nonce', $nonce);

        $isLocal = app()->environment(['local', 'testing']);

        if ($isLocal) {
            // Development-friendly CSP - more permissive for local development
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-eval' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com http: ws:",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https: http:",
                "connect-src 'self' https://api.anthropic.com https://api.stripe.com http: ws:",
                "media-src 'self'",
                "object-src 'none'",
                "child-src 'self'",
                "worker-src 'self'",
                "manifest-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ];
        } else {
            // Production CSP (more restrictive)
            $policies = [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://unpkg.com https://js.stripe.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' https://api.anthropic.com https://api.stripe.com",
                "media-src 'self'",
                "object-src 'none'",
                "child-src 'self'",
                "worker-src 'self'",
                "manifest-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "upgrade-insecure-requests",
            ];
        }

        return implode('; ', $policies);
    }
}