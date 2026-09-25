<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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

        // Share unread alerts count, default device, and device status notifications
        View::composer('layouts.app', function ($view) {
            static $cachedData = null;

            if ($cachedData !== null) {
                $view->with($cachedData);
                return;
            }

            $user = Auth::user();
            if (! $user) {
                $cachedData = [
                    'globalUnreadAlertsCount' => 0,
                    'globalDevice' => null,
                    'globalNotifications' => [],
                ];
                $view->with($cachedData);
                return;
            }

            try {
                $userDevice = $user->devices()
                    ->with(['socketChannels', 'threshold'])
                    ->first();

                $notifications = [];
                $unreadAlertsCount = 0;

                if ($userDevice) {
                    // 1. Alarms / Peringatan Keamanan (Sesuai LCD: Overload, Suhu Tinggi, Asap)
                    $unresolvedAlerts = $userDevice->alerts()
                        ->where('is_resolved', false)
                        ->latest()
                        ->take(5)
                        ->get();

                    $unreadAlertsCount = $unresolvedAlerts->count();

                    foreach ($unresolvedAlerts as $alert) {
                        $label = match ($alert->alert_type) {
                            'overcurrent', 'overload' => 'Peringatan Beban Berlebih (Overload)',
                            'overvoltage' => 'Peringatan Tegangan Berlebih',
                            'high_temperature', 'temperature' => 'Peringatan Suhu Tinggi Enclosure',
                            'smoke' => 'Peringatan Asap Terdeteksi (MQ-2)',
                            default => 'Peringatan Keamanan: ' . ucfirst((string) $alert->alert_type),
                        };

                        $action = $alert->action_taken ? " ({$alert->action_taken})" : '';
                        $notifications[] = [
                            'type' => 'alarm',
                            'category' => 'danger',
                            'title' => $label,
                            'message' => "Terdeteksi {$alert->trigger_value} (Batas: {$alert->threshold_value}){$action}",
                            'badge' => 'ALARM LCD',
                            'time' => $alert->created_at?->diffForHumans() ?? 'Baru saja',
                            'is_critical' => true,
                        ];
                    }

                    // 2. Status Koneksi Alat (Online / Terputus)
                    if ($userDevice->status === 'online') {
                        $notifications[] = [
                            'type' => 'connection',
                            'category' => 'success',
                            'title' => 'Sistem Aktif & Terhubung',
                            'message' => 'ESP32 terhubung ke HiveMQ broker, RSSI: ' . ($userDevice->wifi_rssi ?? 0) . ' dBm.',
                            'badge' => 'ONLINE',
                            'time' => $userDevice->last_seen_at ? $userDevice->last_seen_at->diffForHumans() : 'Aktif sekarang',
                            'is_critical' => false,
                        ];
                    } else {
                        $notifications[] = [
                            'type' => 'connection',
                            'category' => 'warning',
                            'title' => 'Koneksi Terputus (Offline)',
                            'message' => 'ESP32 tidak terhubung atau sambungan WiFi terputus.',
                            'badge' => 'OFFLINE',
                            'time' => $userDevice->last_seen_at ? $userDevice->last_seen_at->diffForHumans() : 'Offline',
                            'is_critical' => false,
                        ];
                    }

                    // 3. Status Beban / Soket Aktif & Deteksi Beban
                    $channels = $userDevice->socketChannels;
                    $loadDetected = false;
                    foreach ($channels as $socket) {
                        $latestLog = $socket->telemetryLogs()->latest('recorded_at')->first();
                        $power = $latestLog ? (float) $latestLog->power : 0.0;
                        $current = $latestLog ? (float) $latestLog->current : 0.0;

                        if ($socket->is_active && $power > 1.0) {
                            $loadDetected = true;
                            $notifications[] = [
                                'type' => 'load',
                                'category' => 'info',
                                'title' => "Beban Terdeteksi: {$socket->name}",
                                'message' => "Beban aktif terbaca {$power} W ({$current} A).",
                                'badge' => 'BEBAN AKTIF',
                                'time' => $latestLog?->recorded_at ? $latestLog->recorded_at->diffForHumans() : 'Aktif',
                                'is_critical' => false,
                            ];
                        } elseif ($socket->is_active) {
                            $notifications[] = [
                                'type' => 'socket',
                                'category' => 'primary',
                                'title' => "{$socket->name} Menyala (Standby)",
                                'message' => 'Relay ON, beban belum terhubung (0 Watt).',
                                'badge' => 'STANDBY',
                                'time' => 'Aktif',
                                'is_critical' => false,
                            ];
                        } else {
                            $notifications[] = [
                                'type' => 'socket',
                                'category' => 'muted',
                                'title' => "{$socket->name} Nonaktif (Mati)",
                                'message' => 'Relay sakelar dalam posisi OFF.',
                                'badge' => 'OFF',
                                'time' => 'Nonaktif',
                                'is_critical' => false,
                            ];
                        }
                    }

                    // 4. Log Aktivitas Terakhir (jika ada)
                    $recentActivities = $userDevice->activities()->latest()->take(3)->get();
                    foreach ($recentActivities as $act) {
                        $notifications[] = [
                            'type' => 'activity',
                            'category' => 'neutral',
                            'title' => $act->title,
                            'message' => $act->description,
                            'badge' => 'LOG',
                            'time' => $act->created_at?->diffForHumans() ?? 'Baru saja',
                            'is_critical' => false,
                        ];
                    }
                }

                $cachedData = [
                    'globalUnreadAlertsCount' => $unreadAlertsCount,
                    'globalDevice' => $userDevice,
                    'globalNotifications' => $notifications,
                ];
            } catch (\Throwable $e) {
                $cachedData = [
                    'globalUnreadAlertsCount' => 0,
                    'globalDevice' => null,
                    'globalNotifications' => [],
                ];
            }

            $view->with($cachedData);
        });
    }
}
