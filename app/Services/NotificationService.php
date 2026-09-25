<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\SensorLog;

class NotificationService
{
    /**
     * Get dynamic structured notifications directly from the database for a given device.
     */
    public function getNotificationsForDevice(?Device $device): array
    {
        if (! $device) {
            return [
                'unread_count' => 0,
                'notifications' => [],
            ];
        }

        $notifications = [];

        // 1. Peringatan Keamanan & Alarm dari tabel `device_alerts`
        $alerts = DeviceAlert::where('device_id', $device->id)
            ->with('socketChannel')
            ->latest()
            ->take(8)
            ->get();

        $unreadCount = DeviceAlert::where('device_id', $device->id)
            ->where('is_resolved', false)
            ->count();

        foreach ($alerts as $alert) {
            $type = strtoupper((string) $alert->alert_type);
            $socketName = $alert->socketChannel?->name ?? ($alert->socket_channel_id ? "Soket {$alert->socket_channel_id}" : null);

            [$label, $badge, $category] = match ($type) {
                'OVER_CURRENT', 'OVERCURRENT', 'OVERLOAD' => [
                    'Peringatan Beban Berlebih (Overload)',
                    'OVERLOAD',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
                'OVER_VOLTAGE', 'OVERVOLTAGE' => [
                    'Peringatan Tegangan Berlebih PLN',
                    'TEGANGAN',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
                'OVER_TEMPERATURE', 'TEMPERATURE', 'HIGH_TEMPERATURE' => [
                    'Peringatan Suhu Tinggi Enclosure',
                    'SUHU TINGGI',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
                'SMOKE_DETECTED', 'SMOKE', 'GAS_DETECTED' => [
                    'Bahaya Asap / Gas Terdeteksi (MQ-2)',
                    'BAHAYA ASAP',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
                'TRIP_LOCKOUT', 'LOCKOUT' => [
                    'Kunci Pengaman Trip Aktif',
                    'TRIP LOCK',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
                default => [
                    'Peringatan Keamanan: ' . ucfirst(str_replace('_', ' ', strtolower($type))),
                    'ALARM',
                    $alert->is_resolved ? 'muted' : 'danger',
                ],
            };

            $valText = "Terdeteksi {$alert->trigger_value}";
            if ($alert->threshold_value > 0) {
                $valText .= " (Batas: {$alert->threshold_value})";
            }
            if ($socketName) {
                $valText .= " pada {$socketName}";
            }
            if ($alert->action_taken) {
                $valText .= " — Tindakan: {$alert->action_taken}";
            }

            $notifications[] = [
                'id' => 'alert_' . $alert->id,
                'type' => 'alarm',
                'category' => $category,
                'title' => $label,
                'message' => $valText,
                'badge' => $alert->is_resolved ? 'TERATASI' : $badge,
                'time' => $alert->created_at?->diffForHumans() ?? 'Baru saja',
                'raw_time' => $alert->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'is_critical' => ! $alert->is_resolved,
            ];
        }

        // 2. Status Koneksi Perangkat dari tabel `devices`
        $isOnline = ($device->status === 'online');
        $notifications[] = [
            'id' => 'device_status',
            'type' => 'connection',
            'category' => $isOnline ? 'success' : 'warning',
            'title' => $isOnline ? 'Sistem Aktif & Terhubung' : 'Koneksi Alat Terputus (Offline)',
            'message' => $isOnline
                ? "ESP32 online via HiveMQ TLS. Sinyal WiFi: " . ($device->wifi_rssi ?? 0) . " dBm (IP: " . ($device->ip_address ?? '127.0.0.1') . ")."
                : "Perangkat ESP32 offline. Terakhir terlihat: " . ($device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah') . ".",
            'badge' => $isOnline ? 'ONLINE' : 'OFFLINE',
            'time' => $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Sekarang',
            'raw_time' => $device->last_seen_at?->toIso8601String() ?? now()->toIso8601String(),
            'is_critical' => ! $isOnline,
        ];

        // 3. Deteksi Beban Soket Realtime dari tabel `socket_channels` dan `sensor_logs`
        $latestSensor = SensorLog::where('device_id', $device->id)
            ->latest('recorded_at')
            ->first();

        $channels = $device->socketChannels;
        foreach ($channels as $socket) {
            $num = $socket->channel_number;
            $power = 0.0;
            $current = 0.0;

            if ($latestSensor) {
                $power = ($num === 1) ? (float) $latestSensor->power_1 : (float) $latestSensor->power_2;
                $current = ($num === 1) ? (float) $latestSensor->current_1 : (float) $latestSensor->current_2;
            }

            if ($socket->is_active && ($power > 1.0 || $current > 0.05)) {
                $notifications[] = [
                    'id' => 'socket_load_' . $socket->id,
                    'type' => 'load',
                    'category' => 'info',
                    'title' => "Beban Terdeteksi: {$socket->name}",
                    'message' => "Beban aktif terbaca {$power} W ({$current} A).",
                    'badge' => 'BEBAN AKTIF',
                    'time' => $latestSensor?->recorded_at ? $latestSensor->recorded_at->diffForHumans() : 'Aktif',
                    'raw_time' => $latestSensor?->recorded_at?->toIso8601String() ?? now()->toIso8601String(),
                    'is_critical' => false,
                ];
            } elseif ($socket->is_active) {
                $notifications[] = [
                    'id' => 'socket_standby_' . $socket->id,
                    'type' => 'socket',
                    'category' => 'primary',
                    'title' => "{$socket->name} Menyala (Standby)",
                    'message' => "Relay ON, beban belum terhubung (0 Watt).",
                    'badge' => 'STANDBY',
                    'time' => 'Aktif',
                    'raw_time' => now()->toIso8601String(),
                    'is_critical' => false,
                ];
            } else {
                $notifications[] = [
                    'id' => 'socket_off_' . $socket->id,
                    'type' => 'socket',
                    'category' => 'muted',
                    'title' => "{$socket->name} Nonaktif (Mati)",
                    'message' => "Relay sakelar dalam posisi OFF.",
                    'badge' => 'OFF',
                    'time' => 'Nonaktif',
                    'raw_time' => now()->toIso8601String(),
                    'is_critical' => false,
                ];
            }
        }

        // 4. Riwayat Aktivitas Terakhir dari tabel `activity_logs`
        $activities = ActivityLog::where('device_id', $device->id)
            ->latest()
            ->take(4)
            ->get();

        foreach ($activities as $act) {
            $notifications[] = [
                'id' => 'activity_' . $act->id,
                'type' => 'activity',
                'category' => 'neutral',
                'title' => $act->title,
                'message' => $act->description ?? 'Aktivitas sistem tercatat.',
                'badge' => strtoupper((string) ($act->event_type ?? 'LOG')),
                'time' => $act->created_at?->diffForHumans() ?? 'Baru saja',
                'raw_time' => $act->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'is_critical' => false,
            ];
        }

        return [
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ];
    }
}
