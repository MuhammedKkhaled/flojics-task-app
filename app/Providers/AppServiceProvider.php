<?php

namespace App\Providers;

use App\Jobs\SendNotificationDeliveryJob;
use App\Models\Ticket;
use App\Notifications\ChannelManager;
use App\Policies\TicketPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChannelManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        RateLimiter::for('notification-channel', function (SendNotificationDeliveryJob $job): Limit {
            $perMinute = max(
                1,
                (int) config("notifications.channels.{$job->channel}.rate_limit", 60),
            );

            return Limit::perMinute($perMinute)->by($job->channel);
        });
    }
}
