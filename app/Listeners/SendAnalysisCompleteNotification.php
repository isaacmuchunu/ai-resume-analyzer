<?php

namespace App\Listeners;

use App\Events\AnalysisCompleted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAnalysisCompleteNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(AnalysisCompleted $event): void
    {
        $this->notificationService->sendAnalysisComplete(
            $event->resume->user,
            $event->resume,
            $event->analysisResult
        );
    }
}
