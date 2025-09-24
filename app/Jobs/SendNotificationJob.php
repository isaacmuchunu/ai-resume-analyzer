<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $type,
        public string $title,
        public string $message,
        public array $data = []
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->sendToUser(
                $this->user,
                $this->type,
                $this->title,
                $this->message,
                $this->data
            );

            Log::info("Notification job completed for user {$this->user->id}: {$this->type}");

        } catch (\Exception $e) {
            Log::error("Notification job failed for user {$this->user->id}: {$e->getMessage()}", [
                'user_id' => $this->user->id,
                'type' => $this->type,
                'exception' => $e,
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Notification job permanently failed for user {$this->user->id}", [
            'user_id' => $this->user->id,
            'type' => $this->type,
            'exception' => $exception->getMessage(),
        ]);
    }
}
