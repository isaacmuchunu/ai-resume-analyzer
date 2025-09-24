<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Settings', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'initials' => $user->initials,
                'role' => $user->role,
            ],
            'preferences' => $user->preferences ?? [],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $oldData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];

        $user->update($validated);

        // Log the profile update
        ActivityLog::logForUser($user, 'Profile updated', null, [
            'old_data' => $oldData,
            'new_data' => $validated,
            'changes' => array_diff_assoc($validated, $oldData),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match your current password.'
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log the password change
        ActivityLog::logForUser($user, 'Password changed', null, [
            'timestamp' => now(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateNotifications(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'browser_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
            'analysis_complete' => 'boolean',
            'weekly_reports' => 'boolean',
        ]);

        $preferences = $user->preferences ?? [];
        $preferences['notifications'] = $validated;

        $user->update(['preferences' => $preferences]);

        // Log the notification preferences update
        ActivityLog::logForUser($user, 'Notification preferences updated', null, [
            'new_preferences' => $validated,
        ]);

        return back()->with('success', 'Notification preferences updated successfully.');
    }

    public function updateAppearance(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $preferences = $user->preferences ?? [];
        $preferences['appearance'] = $validated;

        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Appearance preferences updated successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // Prevent super admin deletion
        if ($user->role === 'super_admin') {
            return back()->withErrors([
                'account' => 'Super admin accounts cannot be deleted.'
            ]);
        }

        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'The provided password is incorrect.'
            ]);
        }

        // Log the account deletion before deleting
        ActivityLog::logForUser($user, 'Account deletion initiated', null, [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->full_name,
            'timestamp' => now(),
            'ip_address' => $request->ip(),
        ]);

        // Delete user data
        $user->resumes()->delete();
        $user->subscription?->delete();
        $user->analytics()->delete();
        $user->activityLogs()->delete();

        // Delete the user account
        $user->delete();

        return redirect()->route('welcome')->with('success', 'Your account has been successfully deleted.');
    }
}