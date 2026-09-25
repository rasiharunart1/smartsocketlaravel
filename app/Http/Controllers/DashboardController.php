<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DeviceAlert;
use App\Models\DeviceThreshold;
use App\Models\EnvironmentalLog;
use App\Models\SensorLog;
use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use App\Services\MqttService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected MqttService $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function index(Request $request): View
    {
        $device = $request->user()->devices()->firstOrFail();

        $socket1 = SocketChannel::where(
            ['device_id' => $device->id, 'channel_number' => 1],
        )->firstOrFail();

        $socket2 = SocketChannel::where(
            ['device_id' => $device->id, 'channel_number' => 2],
        )->firstOrFail();

        $socket1Telemetry = TelemetryLog::where('socket_channel_id', $socket1->id)
            ->latest('recorded_at')
            ->first();

        $socket2Telemetry = TelemetryLog::where('socket_channel_id', $socket2->id)
            ->latest('recorded_at')
            ->first();

        $envLog = EnvironmentalLog::where('device_id', $device->id)
            ->latest('recorded_at')
            ->first();

        // Unified Sensor Log (PZEM 1 + PZEM 2 + DHT22 + MQ-2 in one table)
        $sensorLog = SensorLog::where('device_id', $device->id)
            ->latest('recorded_at')
            ->first();

        $activities = ActivityLog::where('device_id', $device->id)
            ->latest()
            ->take(6)
            ->get();

        $unreadAlertsCount = DeviceAlert::where('device_id', $device->id)
            ->where('is_resolved', false)
            ->count();

        // Calculate average active power from database
        $avgPower = $sensorLog
            ? $sensorLog->total_power
            : (TelemetryLog::whereIn('socket_channel_id', [$socket1->id, $socket2->id])
                ->where('recorded_at', '>=', now()->subHours(24))
                ->avg('power') ?? 0);

        $threshold = DeviceThreshold::where('device_id', $device->id)->first();

        return view('dashboard', compact(
            'device',
            'socket1',
            'socket2',
            'socket1Telemetry',
            'socket2Telemetry',
            'envLog',
            'sensorLog',
            'activities',
            'unreadAlertsCount',
            'avgPower',
            'threshold'
        ));
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'socket_number' => 'required|integer|in:1,2',
            'state' => 'nullable|boolean',
        ]);

        $device = $request->user()->devices()->firstOrFail();

        $socket = SocketChannel::where('device_id', $device->id)
            ->where('channel_number', $validated['socket_number'])
            ->firstOrFail();

        $newState = isset($validated['state']) ? (bool) $validated['state'] : ! $socket->is_active;

        try {
            $published = $this->mqttService->publishSwitch($device, $socket->channel_number, $newState);
        } catch (Exception $e) {
            $published = false;
        }

        $socket->update([
            'is_active' => $newState,
            'status' => $newState ? 'normal' : 'cutoff',
        ]);

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'SWITCH',
            'title' => "Socket {$socket->channel_number} ".($newState ? 'Dinyalakan' : 'Dimatikan'),
            'description' => 'Pengaktifan melalui web dashboard oleh '.(auth()->user()->name ?? 'User'),
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $newState,
            'status' => $socket->status,
            'mqtt_delivered' => $published,
            'message' => "Socket {$socket->channel_number} berhasil ".($newState ? 'dinyalakan' : 'dimatikan'),
        ]);
    }

    public function reconnect(Request $request): JsonResponse
    {
        $device = $request->user()->devices()->firstOrFail();

        try {
            $published = $this->mqttService->publishReconnect($device);
        } catch (Exception $e) {
            $published = false;
        }

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'RECONNECT',
            'title' => 'Permintaan Rekoneksi WiFi',
            'description' => 'Perintah rekoneksi WiFi dikirim melalui web dashboard',
        ]);

        return response()->json([
            'success' => true,
            'mqtt_delivered' => $published,
            'message' => 'Perintah rekoneksi WiFi berhasil dikirim ke perangkat.',
        ]);
    }

    public function telemetry(Request $request): JsonResponse
    {
        $device = $request->user()->devices()->firstOrFail();

        $socket1 = SocketChannel::where(
            ['device_id' => $device->id, 'channel_number' => 1],
        )->firstOrFail();

        $socket2 = SocketChannel::where(
            ['device_id' => $device->id, 'channel_number' => 2],
        )->firstOrFail();

        $s1Tel = $socket1 ? TelemetryLog::where('socket_channel_id', $socket1->id)->latest('recorded_at')->first() : null;
        $s2Tel = $socket2 ? TelemetryLog::where('socket_channel_id', $socket2->id)->latest('recorded_at')->first() : null;
        $env = EnvironmentalLog::where('device_id', $device->id)->latest('recorded_at')->first();

        // Unified SensorLog
        $sensorLog = SensorLog::where('device_id', $device->id)->latest('recorded_at')->first();

        $unreadAlerts = DeviceAlert::where('device_id', $device->id)
            ->where('is_resolved', false)
            ->count();

        $v1 = $sensorLog ? $sensorLog->voltage_1 : ($s1Tel ? (float) $s1Tel->voltage : 0);
        $c1 = $sensorLog ? $sensorLog->current_1 : ($s1Tel ? (float) $s1Tel->current : 0);
        $p1 = $sensorLog ? $sensorLog->power_1 : ($s1Tel ? (float) $s1Tel->power : 0);
        $e1 = $sensorLog ? $sensorLog->energy_1 : ($s1Tel ? (float) $s1Tel->energy : 0);
        $f1 = $sensorLog ? $sensorLog->frequency_1 : ($s1Tel ? (float) $s1Tel->frequency : 0);
        $pf1 = $sensorLog ? $sensorLog->power_factor_1 : ($s1Tel ? (float) $s1Tel->power_factor : 0);

        $v2 = $sensorLog ? $sensorLog->voltage_2 : ($s2Tel ? (float) $s2Tel->voltage : 0);
        $c2 = $sensorLog ? $sensorLog->current_2 : ($s2Tel ? (float) $s2Tel->current : 0);
        $p2 = $sensorLog ? $sensorLog->power_2 : ($s2Tel ? (float) $s2Tel->power : 0);
        $e2 = $sensorLog ? $sensorLog->energy_2 : ($s2Tel ? (float) $s2Tel->energy : 0);
        $f2 = $sensorLog ? $sensorLog->frequency_2 : ($s2Tel ? (float) $s2Tel->frequency : 0);
        $pf2 = $sensorLog ? $sensorLog->power_factor_2 : ($s2Tel ? (float) $s2Tel->power_factor : 0);

        $temp = $sensorLog ? $sensorLog->temperature : ($env ? (float) $env->temperature : 0);
        $smoke = $sensorLog ? $sensorLog->smoke_ppm : ($env ? (float) $env->smoke_ppm : 0);

        $avgPowerRecent = $sensorLog
            ? $sensorLog->total_power
            : round(TelemetryLog::whereIn('socket_channel_id', array_filter([$socket1?->id, $socket2?->id]))
                ->where('recorded_at', '>=', now()->subHours(24))
                ->avg('power') ?? 0, 1);

        // Dynamic Notification List from Database
        $notifService = app(\App\Services\NotificationService::class);
        $notifData = $notifService->getNotificationsForDevice($device);

        return response()->json([
            'device' => [
                'status' => $device->status,
                'wifi_rssi' => $device->wifi_rssi,
                'ip_address' => $device->ip_address,
                'mac_address' => $device->mac_address,
                'firmware_version' => $device->firmware_version,
                'last_seen' => $device->last_seen_at?->diffForHumans() ?? 'Belum pernah',
            ],
            'socket_1' => [
                'name' => $socket1?->name ?? 'Socket 1',
                'pzem' => $socket1?->pzem_identifier ?? 'PZEM_01',
                'is_active' => (bool) ($socket1?->is_active ?? false),
                'status' => $socket1?->status ?? 'offline',
                'voltage' => $v1,
                'current' => $c1,
                'power' => $p1,
                'energy' => $e1,
                'frequency' => $f1,
                'power_factor' => $pf1,
            ],
            'socket_2' => [
                'name' => $socket2?->name ?? 'Socket 2',
                'pzem' => $socket2?->pzem_identifier ?? 'PZEM_02',
                'is_active' => (bool) ($socket2?->is_active ?? false),
                'status' => $socket2?->status ?? 'offline',
                'voltage' => $v2,
                'current' => $c2,
                'power' => $p2,
                'energy' => $e2,
                'frequency' => $f2,
                'power_factor' => $pf2,
            ],
            'environmental' => [
                'temperature' => $temp,
                'smoke_ppm' => $smoke,
            ],
            'total_power' => round($p1 + $p2, 1),
            'total_energy' => round($e1 + $e2, 3),
            'unread_alerts' => $notifData['unread_count'],
            'notifications' => $notifData['notifications'],
            'avg_power' => $avgPowerRecent,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $device = $request->user()->devices()->first();
        $notifService = app(\App\Services\NotificationService::class);
        $data = $notifService->getNotificationsForDevice($device);

        return response()->json([
            'success' => true,
            'unread_count' => $data['unread_count'],
            'notifications' => $data['notifications'],
        ]);
    }

    public function resolveAllAlerts(Request $request): JsonResponse
    {
        $device = $request->user()->devices()->firstOrFail();

        DeviceAlert::where('device_id', $device->id)
            ->where('is_resolved', false)
            ->update(['is_resolved' => true]);

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'SYSTEM_ALERT',
            'title' => 'Semua Alarm Ditandai Selesai',
            'description' => 'Pengguna menandai semua peringatan/alarm aktif sebagai telah diselesaikan.',
        ]);

        $notifService = app(\App\Services\NotificationService::class);
        $data = $notifService->getNotificationsForDevice($device);

        return response()->json([
            'success' => true,
            'message' => 'Semua peringatan telah ditandai selesai.',
            'unread_count' => $data['unread_count'],
            'notifications' => $data['notifications'],
        ]);
    }
}
