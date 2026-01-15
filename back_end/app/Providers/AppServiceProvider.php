<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\Schema::defaultStringLength(255);

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostCreated::class,
            [\App\Listeners\AwardBadges::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\LikeToggled::class,
            [\App\Listeners\AwardBadges::class, 'handle']
        );

         \Illuminate\Support\Facades\Event::listen(
            \App\Events\ReportSubmitted::class,
            [\App\Listeners\NotifyAdmins::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UserRegistered::class,
            [\App\Listeners\NotifyAdmins::class, 'handle']
        );
    }
}
