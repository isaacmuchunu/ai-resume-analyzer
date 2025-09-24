<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserApiController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'subscription' => $this->getSubscriptionData($user),
            'api_usage' => $this->getApiUsage($user),
            'preferences' => $user->preferences ?? [],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'preferences' => 'sometimes|array',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'preferences' => $user->preferences ?? [],
            ],
        ]);
    }

    /**
     * Regenerate API key
     */
    public function regenerateApiKey(Request $request): JsonResponse
    {
        $user = $request->user();

        $apiKey = 'ara_' . Str::random(40);
        $hashedKey = Hash::make($apiKey);

        $user->update([
            'api_key' => $hashedKey,
            'api_key_expires_at' => now()->addYear(),
            'api_key_regenerated_at' => now(),
        ]);

        return response()->json([
            'message' => 'API key regenerated successfully',
            'api_key' => $apiKey,
            'expires_at' => $user->api_key_expires_at,
            'warning' => 'Store this key securely. It will not be shown again.',
        ]);
    }

    /**
     * Get API usage statistics
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get usage data from the last 30 days
        $thirtyDaysAgo = now()->subDays(30);

        $usage = [
            'current_month' => [
                'api_calls' => $this->getApiCallsCount($user, now()->startOfMonth()),
                'resumes_analyzed' => $this->getResumesAnalyzedCount($user, now()->startOfMonth()),
                'uploads' => $this->getUploadsCount($user, now()->startOfMonth()),
            ],
            'last_30_days' => [
                'api_calls' => $this->getApiCallsCount($user, $thirtyDaysAgo),
                'resumes_analyzed' => $this->getResumesAnalyzedCount($user, $thirtyDaysAgo),
                'uploads' => $this->getUploadsCount($user, $thirtyDaysAgo),
            ],
            'all_time' => [
                'api_calls' => $this->getApiCallsCount($user),
                'resumes_analyzed' => $this->getResumesAnalyzedCount($user),
                'uploads' => $this->getUploadsCount($user),
                'account_age_days' => $user->created_at->diffInDays(now()),
            ],
            'rate_limits' => [
                'api' => '100 requests per hour',
                'uploads' => '10 per hour',
                'analysis' => '20 per hour',
            ],
        ];

        return response()->json($usage);
    }

    /**
     * Get subscription data
     */
    private function getSubscriptionData($user): ?array
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return null;
        }

        return [
            'plan' => $subscription->plan,
            'status' => $subscription->status,
            'expires_at' => $subscription->period_ends_at,
            'features' => $subscription->getFeatures(),
        ];
    }

    /**
     * Get API usage data
     */
    private function getApiUsage($user): array
    {
        return [
            'api_key_expires_at' => $user->api_key_expires_at,
            'api_last_used_at' => $user->api_last_used_at,
            'total_api_calls' => $this->getApiCallsCount($user),
        ];
    }

    /**
     * Get API calls count
     */
    private function getApiCallsCount($user, $since = null): int
    {
        // This would typically come from a usage tracking table
        // For now, we'll return a placeholder
        return 0;
    }

    /**
     * Get resumes analyzed count
     */
    private function getResumesAnalyzedCount($user, $since = null): int
    {
        $query = $user->resumes()->whereNotNull('analysis_status');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->count();
    }

    /**
     * Get uploads count
     */
    private function getUploadsCount($user, $since = null): int
    {
        $query = $user->resumes();

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->count();
    }
}