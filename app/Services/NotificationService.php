<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    public function sendToUser(User $user, string $type, string $title, string $message, array $data = []): bool
    {
        try {
            // Check user preferences
            $preferences = $user->preferences['notifications'] ?? [];

            // Create database notification
            $notification = $user->notifications()->create([
                'type' => $type,
                'data' => array_merge($data, [
                    'title' => $title,
                    'message' => $message,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]),
            ]);

            // Send email if enabled
            if ($this->shouldSendEmail($user, $type)) {
                $this->sendEmailNotification($user, $title, $message, $data);
            }

            // Log the notification
            Log::info("Notification sent to user {$user->id}: {$type}", [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to send notification to user {$user->id}: {$e->getMessage()}", [
                'user_id' => $user->id,
                'type' => $type,
                'exception' => $e,
            ]);

            return false;
        }
    }

    public function sendAnalysisComplete(User $user, $resume, $analysisResult): bool
    {
        $title = 'Resume Analysis Complete';
        $message = "Your resume '{$resume->original_filename}' has been analyzed successfully.";

        return $this->sendToUser($user, 'analysis_complete', $title, $message, [
            'resume_id' => $resume->id,
            'analysis_id' => $analysisResult->id,
            'score' => $analysisResult->overall_score,
        ]);
    }

    public function sendSubscriptionExpiring(User $user, $subscription, int $daysLeft): bool
    {
        $title = 'Subscription Expiring Soon';
        $message = "Your {$subscription->plan} subscription will expire in {$daysLeft} days.";

        return $this->sendToUser($user, 'subscription_expiring', $title, $message, [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan,
            'days_left' => $daysLeft,
            'expires_at' => $subscription->period_ends_at,
        ]);
    }

    public function sendSubscriptionExpired(User $user, $subscription): bool
    {
        $title = 'Subscription Expired';
        $message = "Your {$subscription->plan} subscription has expired. Upgrade to continue using premium features.";

        return $this->sendToUser($user, 'subscription_expired', $title, $message, [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan,
            'expired_at' => $subscription->period_ends_at,
        ]);
    }

    public function sendWeeklyReport(User $user, array $stats): bool
    {
        $title = 'Weekly Resume Analysis Report';
        $message = "Here's your weekly summary: {$stats['resumes_analyzed']} resumes analyzed, average score: {$stats['average_score']}%";

        return $this->sendToUser($user, 'weekly_report', $title, $message, $stats);
    }

    public function sendWelcome(User $user): bool
    {
        $title = 'Welcome to AI Resume Analyzer!';
        $message = 'Thank you for joining! Start by uploading your first resume for analysis.';

        return $this->sendToUser($user, 'welcome', $title, $message);
    }

    public function sendPasswordChanged(User $user): bool
    {
        $title = 'Password Changed';
        $message = 'Your password has been successfully changed.';

        return $this->sendToUser($user, 'password_changed', $title, $message, [
            'changed_at' => now(),
            'ip_address' => request()->ip(),
        ]);
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        try {
            $notification = $user->notifications()->findOrFail($notificationId);
            $notification->update(['read_at' => now()]);

            return true;
        } catch (Exception $e) {
            Log::error("Failed to mark notification as read: {$e->getMessage()}");
            return false;
        }
    }

    public function markAllAsRead(User $user): bool
    {
        try {
            $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
            return true;
        } catch (Exception $e) {
            Log::error("Failed to mark all notifications as read: {$e->getMessage()}");
            return false;
        }
    }

    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    private function shouldSendEmail(User $user, string $type): bool
    {
        $preferences = $user->preferences['notifications'] ?? [];

        // Check if email notifications are enabled
        if (!($preferences['email_notifications'] ?? true)) {
            return false;
        }

        // Check specific type preferences
        switch ($type) {
            case 'analysis_complete':
                return $preferences['analysis_complete'] ?? true;
            case 'weekly_report':
                return $preferences['weekly_reports'] ?? false;
            case 'marketing_emails':
                return $preferences['marketing_emails'] ?? false;
            case 'subscription_expiring':
            case 'subscription_expired':
                return true; // Always send subscription notifications
            default:
                return true;
        }
    }

    private function sendEmailNotification(User $user, string $title, string $message, array $data = []): void
    {
        try {
            // For now, we'll log the email. In production, you'd send actual emails
            Log::info("Email notification would be sent to {$user->email}", [
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);

            // Uncomment and configure when email service is set up:
            /*
            Mail::raw($message, function ($mail) use ($user, $title) {
                $mail->to($user->email)
                     ->subject($title);
            });
            */

        } catch (Exception $e) {
            Log::error("Failed to send email notification: {$e->getMessage()}");
        }
    }
}
