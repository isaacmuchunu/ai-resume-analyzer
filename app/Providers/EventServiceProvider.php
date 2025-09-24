<?php

namespace App\Providers;

use App\Events\AnalysisCompleted;
use App\Listeners\SendAnalysisCompleteNotification;
use App\Listeners\LoginEventListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AnalysisCompleted::class => [
            SendAnalysisCompleteNotification::class,
        ],
        Login::class => [
            [LoginEventListener::class, 'handleLogin'],
        ],
        Failed::class => [
            [LoginEventListener::class, 'handleFailedLogin'],
        ],
        Lockout::class => [
            [LoginEventListener::class, 'handleLockout'],
        ],
    ];

    public function boot(): void
    {
        //
    }
}
