<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        // Share unread alerts count and default device status globally to all views
        View::composer('*', function ($view) {
            if (Schema::hasTable('devices') && Schema::hasTable('device_alerts')) {
                $userDevice = Auth::user()?->devices()->first();
                $unreadAlertsCount = $userDevice
                    ? $userDevice->alerts()->where('is_resolved', false)->count()
                    : 0;

                $view->with('globalUnreadAlertsCount', $unreadAlertsCount);
                $view->with('globalDevice', $userDevice);
            }
        });
    }
}
