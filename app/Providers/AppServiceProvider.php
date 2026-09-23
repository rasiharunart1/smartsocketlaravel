<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\DeviceAlert;
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
                $deviceUid = config('mqtt.default_device_uid', 'ESP32_SOCKET_01');
                $defaultDevice = Device::where('device_uid', $deviceUid)->first();
                $unreadAlertsCount = $defaultDevice
                    ? DeviceAlert::where('device_id', $defaultDevice->id)->where('is_resolved', false)->count()
                    : 0;

                $view->with('globalUnreadAlertsCount', $unreadAlertsCount);
                $view->with('globalDevice', $defaultDevice);
            }
        });
    }
}
