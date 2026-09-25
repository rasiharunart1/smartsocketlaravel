<?php

namespace App\Providers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Share dynamic device notifications, unread alert counter, and active device to layouts.app
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if (! $user) {
                $view->with([
                    'globalUnreadAlertsCount' => 0,
                    'globalDevice' => null,
                    'globalNotifications' => [],
                ]);
                return;
            }

            try {
                $userDevice = $user->devices()
                    ->with(['socketChannels', 'threshold'])
                    ->first();

                $notifService = app(NotificationService::class);
                $notifData = $notifService->getNotificationsForDevice($userDevice);

                $view->with([
                    'globalUnreadAlertsCount' => $notifData['unread_count'],
                    'globalDevice' => $userDevice,
                    'globalNotifications' => $notifData['notifications'],
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'globalUnreadAlertsCount' => 0,
                    'globalDevice' => null,
                    'globalNotifications' => [],
                ]);
            }
        });
    }
}
