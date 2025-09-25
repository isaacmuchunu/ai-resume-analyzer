<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendToUser(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): bool {
        try {
            // Log the notification
            Log::info('Sending notification', [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
            ]);

            // Send email notification
            $this->sendEmailNotification($user, $type, $title, $message, $data);

            // Store in database for in-app notifications
            $this->storeNotification($user, $type, $title, $message, $data);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendAnalysisCompleted(User $user, $resume, $analysis): bool
    {
        $title = "Resume Analysis Complete";
        $message = "Your resume '{$resume->original_filename}' has been analyzed. Overall score: {$analysis->overall_score}/100";

        return $this->sendToUser($user, 'analysis_completed', $title, $message, [
            'resume_id' => $resume->id,
            'analysis_id' => $analysis->id,
            'overall_score' => $analysis->overall_score,
        ]);
    }

    public function sendWelcome(User $user): bool
    {
        $title = "Welcome to AI Resume Analyzer";
        $message = "Welcome aboard! Start by uploading your resume for AI-powered analysis.";

        return $this->sendToUser($user, 'welcome', $title, $message);
    }

    public function sendPasswordReset(User $user, string $resetUrl): bool
    {
        $title = "Password Reset Request";
        $message = "Click the link to reset your password. This link expires in 1 hour.";

        return $this->sendToUser($user, 'password_reset', $title, $message, [
            'reset_url' => $resetUrl,
        ]);
    }

    public function sendSubscriptionUpdate(User $user, string $status, array $data = []): bool
    {
        $title = match($status) {
            'activated' => 'Subscription Activated',
            'cancelled' => 'Subscription Cancelled',
            'renewed' => 'Subscription Renewed',
            'payment_failed' => 'Payment Failed',
            default => 'Subscription Update',
        };

        $message = match($status) {
            'activated' => 'Your subscription has been activated. Enjoy premium features!',
            'cancelled' => 'Your subscription has been cancelled. You can reactivate anytime.',
            'renewed' => 'Your subscription has been renewed successfully.',
            'payment_failed' => 'We could not process your payment. Please update your payment method.',
            default => 'Your subscription status has been updated.',
        };

        return $this->sendToUser($user, 'subscription_' . $status, $title, $message, $data);
    }

    private function sendEmailNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data
    ): void {
        // Simple email sending - in production, use proper mail templates
        try {
            Mail::raw($message, function ($mail) use ($user, $title) {
                $mail->to($user->email, $user->full_name)
                     ->subject($title);
            });
        } catch (\Exception $e) {
            Log::warning('Failed to send email notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function storeNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data
    ): void {
        // Store notification in database
        // This would require a notifications table
        try {
            \DB::table('notifications')->insert([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store notification in database', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getUserNotifications(User $user, bool $unreadOnly = false): array
    {
        try {
            $query = \DB::table('notifications')->where('user_id', $user->id);

            if ($unreadOnly) {
                $query->whereNull('read_at');
            }

            return $query->orderBy('created_at', 'desc')
                         ->limit(20)
                         ->get()
                         ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to fetch user notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function markAsRead(User $user, int $notificationId): bool
    {
        try {
            return \DB::table('notifications')
                     ->where('id', $notificationId)
                     ->where('user_id', $user->id)
                     ->update(['read_at' => now()]) > 0;

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => $user->id,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function markAllAsRead(User $user): bool
    {
        try {
            return \DB::table('notifications')
                     ->where('user_id', $user->id)
                     ->whereNull('read_at')
                     ->update(['read_at' => now()]) >= 0;

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getUnreadCount(User $user): int
    {
        try {
            return \DB::table('notifications')
                     ->where('user_id', $user->id)
                     ->whereNull('read_at')
                     ->count();

        } catch (\Exception $e) {
            Log::error('Failed to get unread notification count', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}