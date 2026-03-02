<?php

namespace App\Providers;

use App\Listeners\TaskManagement\SendOverdueTaskNotification;
use App\Events\TaskManagement\TaskUpdated;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            TaskUpdated::class,
            SendOverdueTaskNotification::class,
        );
    }
}
