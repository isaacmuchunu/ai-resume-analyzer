<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['subscription', 'resumes']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by subscription plan
        if ($request->filled('plan')) {
            $query->whereHas('subscription', function ($q) use ($request) {
                $q->where('plan', $request->plan);
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(25)->through(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'resumes_count' => $user->resumes->count(),
                'current_plan' => $user->getCurrentPlan(),
                'subscription_status' => $user->getSubscriptionStatus(),
                'last_activity' => $user->activityLogs()->latest()->first()?->created_at,
            ];
        });

        $filters = [
            'search' => $request->search,
            'role' => $request->role,
            'plan' => $request->plan,
            'sort' => $sortField,
            'direction' => $sortDirection,
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function show(User $user)
    {
        $user->load(['resumes.analysisResults', 'subscription', 'analytics', 'activityLogs']);

        $userDetails = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'current_plan' => $user->getCurrentPlan(),
            'subscription_status' => $user->getSubscriptionStatus(),
        ];

        $subscription = $user->subscription ? [
            'plan' => $user->subscription->plan,
            'status' => $user->subscription->status,
            'resumes_limit' => $user->subscription->resumes_limit,
            'resumes_used' => $user->subscription->resumes_used,
            'period_starts_at' => $user->subscription->period_starts_at,
            'period_ends_at' => $user->subscription->period_ends_at,
        ] : null;

        $resumes = $user->resumes->map(function ($resume) {
            $latestAnalysis = $resume->analysisResults->first();
            return [
                'id' => $resume->id,
                'original_filename' => $resume->original_filename,
                'analysis_status' => $resume->analysis_status,
                'created_at' => $resume->created_at,
                'latest_analysis' => $latestAnalysis ? [
                    'overall_score' => $latestAnalysis->overall_score,
                    'created_at' => $latestAnalysis->created_at,
                ] : null,
            ];
        });

        $recentActivity = $user->activityLogs()
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($log) {
                return [
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'properties' => $log->properties,
                ];
            });

        return Inertia::render('Admin/Users/Show', [
            'user' => $userDetails,
            'subscription' => $subscription,
            'resumes' => $resumes,
            'recent_activity' => $recentActivity,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'admin', 'super_admin'])],
        ]);

        $oldData = $user->only(['first_name', 'last_name', 'email', 'role']);

        $user->update($request->only(['first_name', 'last_name', 'email', 'role']));

        // Log the update
        ActivityLog::logForUser(auth()->user(), 'Admin updated user', $user, [
            'old_data' => $oldData,
            'new_data' => $user->only(['first_name', 'last_name', 'email', 'role']),
            'admin_user' => auth()->user()->email,
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot delete super admin user.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        // Log the deletion before deleting
        ActivityLog::logForUser(auth()->user(), 'Admin deleted user', null, [
            'deleted_user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ],
            'admin_user' => auth()->user()->email,
        ]);

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function updateSubscription(Request $request, User $user)
    {
        $request->validate([
            'plan' => 'required|in:free,basic,pro,enterprise',
            'status' => 'required|in:active,cancelled,expired,suspended',
        ]);

        $subscription = $user->subscription;

        if (!$subscription) {
            // Create new subscription
            $planLimits = UserSubscription::getPlanLimits($request->plan);
            UserSubscription::create([
                'user_id' => $user->id,
                'plan' => $request->plan,
                'status' => $request->status,
                'resumes_limit' => $planLimits['resumes_limit'],
                'resumes_used' => 0,
                'period_starts_at' => now(),
                'period_ends_at' => now()->addMonth(),
                'features' => $planLimits['features'],
            ]);
        } else {
            // Update existing subscription
            $oldData = $subscription->only(['plan', 'status']);
            $planLimits = UserSubscription::getPlanLimits($request->plan);

            $subscription->update([
                'plan' => $request->plan,
                'status' => $request->status,
                'resumes_limit' => $planLimits['resumes_limit'],
                'features' => $planLimits['features'],
            ]);

            // Log the update
            ActivityLog::logForUser(auth()->user(), 'Admin updated user subscription', $user, [
                'old_data' => $oldData,
                'new_data' => ['plan' => $request->plan, 'status' => $request->status],
                'admin_user' => auth()->user()->email,
            ]);
        }

        return back()->with('success', 'Subscription updated successfully.');
    }
}