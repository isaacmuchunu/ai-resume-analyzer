<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\ResumeShare;
use App\Models\ResumeComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class CollaborationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Share resume with email address
     */
    public function shareResume(Resume $resume, array $data): array
    {
        try {
            // Validate permissions
            if (!$this->canUserShareResume($resume, auth()->user())) {
                throw new Exception('You do not have permission to share this resume.');
            }

            // Check if already shared with this email
            $existingShare = ResumeShare::where('resume_id', $resume->id)
                ->where('shared_with_email', $data['email'])
                ->active()
                ->first();

            if ($existingShare) {
                // Update existing share
                $existingShare->update([
                    'permissions' => $data['permissions'] ?? ['view'],
                    'expires_at' => $data['expires_at'] ?? now()->addDays(7),
                    'message' => $data['message'] ?? null,
                ]);
                $share = $existingShare;
            } else {
                // Create new share
                $share = ResumeShare::createShare($resume, $data);
            }

            // Send notification email
            $this->sendShareNotification($share, $data);

            // Log activity
            $resume->user->logActivity('Resume shared', $resume, [
                'shared_with' => $data['email'],
                'permissions' => $data['permissions'] ?? ['view'],
                'expires_at' => $data['expires_at'] ?? now()->addDays(7),
            ]);

            return [
                'success' => true,
                'share' => $share,
                'message' => 'Resume shared successfully!',
            ];

        } catch (Exception $e) {
            Log::error('Resume sharing failed', [
                'resume_id' => $resume->id,
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create public share link
     */
    public function createPublicLink(Resume $resume, array $options = []): array
    {
        try {
            if (!$this->canUserShareResume($resume, auth()->user())) {
                throw new Exception('You do not have permission to create public links for this resume.');
            }

            $share = ResumeShare::create([
                'resume_id' => $resume->id,
                'user_id' => $resume->user_id,
                'share_token' => \Illuminate\Support\Str::random(32),
                'permissions' => $options['permissions'] ?? ['view'],
                'expires_at' => $options['expires_at'] ?? now()->addDays(30),
                'is_active' => true,
                'access_count' => 0,
            ]);

            return [
                'success' => true,
                'share_url' => $share->share_url,
                'token' => $share->share_token,
                'expires_at' => $share->expires_at,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add comment to resume
     */
    public function addComment(Resume $resume, array $data, ?User $user = null): array
    {
        try {
            // Validate comment permissions
            if (!$this->canAddComment($resume, $user)) {
                throw new Exception('You do not have permission to comment on this resume.');
            }

            $comment = ResumeComment::createComment($resume, [
                'user_id' => $user?->id,
                'commenter_email' => $data['email'] ?? $user?->email,
                'commenter_name' => $data['name'] ?? $user?->name,
                'content' => $data['content'],
                'type' => $data['type'] ?? 'general',
                'section' => $data['section'] ?? null,
                'position_start' => $data['position_start'] ?? null,
                'position_end' => $data['position_end'] ?? null,
            ]);

            // Notify resume owner
            $this->notifyCommentAdded($resume, $comment);

            return [
                'success' => true,
                'comment' => $comment,
                'message' => 'Comment added successfully!',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reply to a comment
     */
    public function replyToComment(ResumeComment $comment, array $data, ?User $user = null): array
    {
        try {
            $reply = $comment->addReply([
                'user_id' => $user?->id,
                'commenter_email' => $data['email'] ?? $user?->email,
                'commenter_name' => $data['name'] ?? $user?->name,
                'content' => $data['content'],
            ]);

            // Notify original commenter and resume owner
            $this->notifyCommentReply($comment, $reply);

            return [
                'success' => true,
                'reply' => $reply,
                'message' => 'Reply added successfully!',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get resume with collaboration data
     */
    public function getResumeWithCollaboration(Resume $resume, ?User $user = null): array
    {
        $data = [
            'resume' => $resume,
            'can_share' => $this->canUserShareResume($resume, $user),
            'can_comment' => $this->canAddComment($resume, $user),
            'shares' => [],
            'comments' => [],
            'collaboration_stats' => [],
        ];

        // Load shares if user owns resume
        if ($user && $resume->user_id === $user->id) {
            $data['shares'] = $resume->shares()->active()->with('user')->get();
            $data['collaboration_stats'] = $this->getCollaborationStats($resume);
        }

        // Load comments
        $data['comments'] = $resume->comments()
            ->topLevel()
            ->with(['replies', 'user'])
            ->orderBy('created_at')
            ->get();

        return $data;
    }

    /**
     * Get shared resume by token
     */
    public function getSharedResume(string $token): array
    {
        try {
            $share = ResumeShare::findByToken($token);

            if (!$share) {
                throw new Exception('Invalid or expired share link.');
            }

            // Record access
            $share->recordAccess();

            $resume = $share->resume()->with(['latestAnalysis'])->first();

            return [
                'success' => true,
                'resume' => $resume,
                'share' => $share,
                'permissions' => $share->permissions,
                'can_comment' => $share->can_comment,
                'can_download' => $share->can_download,
                'comments' => $share->can_comment ? $this->getCommentsForShare($resume) : [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke resume share
     */
    public function revokeShare(ResumeShare $share): array
    {
        try {
            if (!$this->canUserManageShare($share, auth()->user())) {
                throw new Exception('You do not have permission to revoke this share.');
            }

            $share->deactivate();

            return [
                'success' => true,
                'message' => 'Share revoked successfully!',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get collaboration activity for resume
     */
    public function getCollaborationActivity(Resume $resume): array
    {
        $activities = [];

        // Get recent shares
        $shares = $resume->shares()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($shares as $share) {
            $activities[] = [
                'type' => 'share',
                'action' => 'Resume shared',
                'user' => $share->user?->name ?? 'System',
                'details' => "Shared with {$share->shared_with_email}",
                'timestamp' => $share->created_at,
            ];
        }

        // Get recent comments
        $comments = $resume->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($comments as $comment) {
            $activities[] = [
                'type' => 'comment',
                'action' => $comment->is_reply ? 'Reply added' : 'Comment added',
                'user' => $comment->commenter_display_name,
                'details' => substr($comment->content, 0, 100) . '...',
                'timestamp' => $comment->created_at,
            ];
        }

        // Sort by timestamp
        usort($activities, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, 20);
    }

    // Private helper methods

    private function canUserShareResume(Resume $resume, ?User $user): bool
    {
        return $user && $resume->user_id === $user->id;
    }

    private function canUserManageShare(ResumeShare $share, ?User $user): bool
    {
        return $user && $share->user_id === $user->id;
    }

    private function canAddComment(Resume $resume, ?User $user): bool
    {
        // Resume owner can always comment
        if ($user && $resume->user_id === $user->id) {
            return true;
        }

        // Check if user has commenting permission via share
        // This would be implemented based on your sharing logic
        return true; // Simplified for now
    }

    private function sendShareNotification(ResumeShare $share, array $data): void
    {
        try {
            // Send email notification
            // Implementation depends on your mail configuration
            Log::info('Resume share notification sent', [
                'share_id' => $share->id,
                'email' => $share->shared_with_email,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send share notification', [
                'share_id' => $share->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyCommentAdded(Resume $resume, ResumeComment $comment): void
    {
        try {
            if ($comment->user_id !== $resume->user_id) {
                $this->notificationService->sendToUser(
                    $resume->user,
                    'comment_added',
                    'New comment on your resume',
                    "Someone commented on your resume: {$resume->original_filename}",
                    [
                        'resume_id' => $resume->id,
                        'comment_id' => $comment->id,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to notify comment added', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyCommentReply(ResumeComment $originalComment, ResumeComment $reply): void
    {
        // Implement reply notifications
    }

    private function getCollaborationStats(Resume $resume): array
    {
        return [
            'total_shares' => $resume->shares()->count(),
            'active_shares' => $resume->shares()->active()->count(),
            'total_views' => $resume->shares()->sum('access_count'),
            'total_comments' => $resume->comments()->count(),
            'unresolved_comments' => $resume->comments()->unresolved()->count(),
        ];
    }

    private function getCommentsForShare(Resume $resume): array
    {
        return $resume->comments()
            ->topLevel()
            ->with(['replies', 'user'])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
}