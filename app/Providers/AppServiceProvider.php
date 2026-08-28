<?php

namespace App\Providers;

use App\Contracts\BroadcastDeliveryChannel;
use App\Contracts\NotificationChannel;
use App\Models\Permission;
use App\Services\Broadcast\ChannelRouter;
use App\Services\LocalNotificationChannel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationChannel::class, LocalNotificationChannel::class);
        $this->app->bind(BroadcastDeliveryChannel::class, ChannelRouter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            return $user->hasPermissionTo($ability) ? true : null;
        });

        try {
            Permission::query()->pluck('slug')->each(
                fn (string $slug) => Gate::define($slug, fn ($user) => $user->hasPermissionTo($slug))
            );
        } catch (\Throwable) {
            //
        }
    }
}
