<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\DeviceThreshold;
use App\Models\EnvironmentalLog;
use App\Models\SensorLog;
use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use App\Services\MqttService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MqttListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen {--device= : Device UID whose stored MQTT credentials should be used}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to MQTT topics from HiveMQ Cloud for Smart Socket telemetry and events';

    /**
     * Execute the console command.
     */
    public function handle(MqttService $mqttService): int
    {
        $this->info('Starting Smart Socket MQTT Daemon...');
        $device = $this->option('device')
            ? Device::where('device_uid', $this->option('device'))->firstOrFail()
            : null;
        $clientId = ($device?->mqtt_client_id ?: config('mqtt.client_id', 'laravel_backend_daemon')).'_sub_'.uniqid();
        $topicDevice = $device?->device_uid ?? '+';
        $host = $device?->mqtt_host ?: config('mqtt.host');
        $port = $device?->mqtt_port ?: config('mqtt.port');

        while (true) {
            try {
                if ($device && (! $device->mqtt_host || ! $device->mqtt_port)) {
                    $this->error('MQTT credentials for this device are not configured.');

                    return self::FAILURE;
                }

                $client = $mqttService->getClient($clientId, $device);
                $settings = $mqttService->getConnectionSettings($device);

                $this->info("Connecting to MQTT broker at {$host}:{$port}...");
                $client->connect($settings, true);
                $this->info('Connected successfully. Subscribing to topics...');

                // Subscribe to telemetry
                $client->subscribe("smartsocket/{$topicDevice}/telemetry", function (string $topic, string $message) {
                    $this->handleTelemetry($topic, $message);
                }, 0);

                // Subscribe to status (LWT)
                $client->subscribe("smartsocket/{$topicDevice}/status", function (string $topic, string $message) {
                    $this->handleStatus($topic, $message);
                }, 1);

                // Subscribe to emergency alert
                $client->subscribe("smartsocket/{$topicDevice}/alert", function (string $topic, string $message) {
                    $this->handleAlert($topic, $message);
                }, 1);

                $this->info('Listening for messages... Press Ctrl+C to exit.');
                $client->loop(true);
            } catch (Exception $e) {
                $this->error('MQTT Worker Exception: '.$e->getMessage());
                Log::warning('MQTT Worker error: '.$e->getMessage());
                $this->warn('Reconnecting in 5 seconds...');
                sleep(5);
            }
        }

        return 0;
    }

    protected function extractDeviceUid(string $topic): ?string
    {
        $parts = explode('/', $topic);

        return $parts[1] ?? null;
    }

    protected function handleTelemetry(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! is_array($data)) {
                Log::warning("Invalid JSON received on [{$topic}]: {$message}");

                return;
            }

            $deviceUid = $data['device_id'] ?? $this->extractDeviceUid($topic);
            if (! $deviceUid) {
                return;
            }

            DB::transaction(function () use ($deviceUid, $data) {
                $device = Device::where('device_uid', $deviceUid)->firstOrFail();

                $device->update([
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);

                $recordedAt = isset($data['timestamp'])
                    ? Carbon::createFromTimestamp($data['timestamp'])
                    : now();

                // 1. Parse Environmental metrics (Single Enclosure: DHT22 & MQ-2)
                $temp = (float) ($data['environmental']['temperature'] ?? $data['temperature'] ?? 0);
                $smoke = (float) ($data['environmental']['smoke_ppm'] ?? $data['smoke_ppm'] ?? 0);

                // 2. Parse Socket 1 PZEM metrics
                $s1 = $data['sockets']['socket_1'] ?? $data['socket_1'] ?? $data;
                $v1 = (float) ($s1['voltage'] ?? $data['voltage_1'] ?? 0);
                $c1 = (float) ($s1['current'] ?? $data['current_1'] ?? 0);
                $p1 = (float) ($s1['power'] ?? $data['power_1'] ?? 0);
                $e1 = (float) ($s1['energy'] ?? $data['energy_1'] ?? 0);
                $f1 = (float) ($s1['frequency'] ?? $data['frequency_1'] ?? 0);
                $pf1 = isset($s1['power_factor']) ? (float) $s1['power_factor'] : (isset($data['power_factor_1']) ? (float) $data['power_factor_1'] : 0);

                // 3. Parse Socket 2 PZEM metrics
                $s2 = $data['sockets']['socket_2'] ?? $data['socket_2'] ?? $data;
                $v2 = (float) ($s2['voltage'] ?? $data['voltage_2'] ?? 0);
                $c2 = (float) ($s2['current'] ?? $data['current_2'] ?? 0);
                $p2 = (float) ($s2['power'] ?? $data['power_2'] ?? 0);
                $e2 = (float) ($s2['energy'] ?? $data['energy_2'] ?? 0);
                $f2 = (float) ($s2['frequency'] ?? $data['frequency_2'] ?? 0);
                $pf2 = isset($s2['power_factor']) ? (float) $s2['power_factor'] : (isset($data['power_factor_2']) ? (float) $data['power_factor_2'] : 0);

                // 4. Update Socket Channel Relay States
                $channel1 = SocketChannel::firstOrCreate(
                    ['device_id' => $device->id, 'channel_number' => 1],
                    ['name' => 'Socket 1', 'pzem_identifier' => 'PZEM_01', 'is_active' => false, 'status' => 'offline']
                );
                if (isset($s1['relay_state'])) {
                    $channel1->update([
                        'is_active' => (strtoupper((string) $s1['relay_state']) === 'ON' || $s1['relay_state'] === true || $s1['relay_state'] === 1),
                    ]);
                }

                $channel2 = SocketChannel::firstOrCreate(
                    ['device_id' => $device->id, 'channel_number' => 2],
                    ['name' => 'Socket 2', 'pzem_identifier' => 'PZEM_02', 'is_active' => false, 'status' => 'offline']
                );
                if (isset($s2['relay_state'])) {
                    $channel2->update([
                        'is_active' => (strtoupper((string) $s2['relay_state']) === 'ON' || $s2['relay_state'] === true || $s2['relay_state'] === 1),
                    ]);
                }

                // 5. Save unified SensorLog (All in one table)
                SensorLog::create([
                    'device_id' => $device->id,
                    'voltage_1' => $v1,
                    'current_1' => $c1,
                    'power_1' => $p1,
                    'energy_1' => $e1,
                    'frequency_1' => $f1,
                    'power_factor_1' => $pf1,
                    'voltage_2' => $v2,
                    'current_2' => $c2,
                    'power_2' => $p2,
                    'energy_2' => $e2,
                    'frequency_2' => $f2,
                    'power_factor_2' => $pf2,
                    'temperature' => $temp,
                    'smoke_ppm' => $smoke,
                    'recorded_at' => $recordedAt,
                ]);

                // Also maintain legacy tables for compatibility
                EnvironmentalLog::create([
                    'device_id' => $device->id,
                    'temperature' => $temp,
                    'smoke_ppm' => $smoke,
                    'recorded_at' => $recordedAt,
                ]);

                TelemetryLog::create([
                    'socket_channel_id' => $channel1->id,
                    'voltage' => $v1,
                    'current' => $c1,
                    'power' => $p1,
                    'energy' => $e1,
                    'frequency' => $f1,
                    'power_factor' => $pf1,
                    'recorded_at' => $recordedAt,
                ]);

                TelemetryLog::create([
                    'socket_channel_id' => $channel2->id,
                    'voltage' => $v2,
                    'current' => $c2,
                    'power' => $p2,
                    'energy' => $e2,
                    'frequency' => $f2,
                    'power_factor' => $pf2,
                    'recorded_at' => $recordedAt,
                ]);

                // 6. Check Safety Thresholds & Trigger Alerts
                $threshold = DeviceThreshold::firstOrCreate(['device_id' => $device->id]);

                if ($threshold->max_temperature > 0 && $temp >= $threshold->max_temperature) {
                    DeviceAlert::create([
                        'device_id' => $device->id,
                        'alert_type' => 'OVER_TEMPERATURE',
                        'trigger_value' => $temp,
                        'threshold_value' => $threshold->max_temperature,
                        'action_taken' => 'WARNING_LOGGED',
                    ]);
                }

                if ($threshold->max_smoke_ppm > 0 && $smoke >= $threshold->max_smoke_ppm) {
                    DeviceAlert::create([
                        'device_id' => $device->id,
                        'alert_type' => 'SMOKE_DETECTED',
                        'trigger_value' => $smoke,
                        'threshold_value' => $threshold->max_smoke_ppm,
                        'action_taken' => 'EMERGENCY_ALERT',
                    ]);
                }

                $maxV = max($v1, $v2);
                if ($threshold->max_voltage > 0 && $maxV >= $threshold->max_voltage) {
                    DeviceAlert::create([
                        'device_id' => $device->id,
                        'alert_type' => 'OVER_VOLTAGE',
                        'trigger_value' => $maxV,
                        'threshold_value' => $threshold->max_voltage,
                        'action_taken' => 'WARNING_LOGGED',
                    ]);
                }

                $maxC = max($c1, $c2);
                if ($threshold->max_current > 0 && $maxC >= $threshold->max_current) {
                    DeviceAlert::create([
                        'device_id' => $device->id,
                        'alert_type' => 'OVER_CURRENT',
                        'trigger_value' => $maxC,
                        'threshold_value' => $threshold->max_current,
                        'action_taken' => 'WARNING_LOGGED',
                    ]);
                }
            });

            $this->line("<info>[Telemetry]</info> Processed data for device: {$deviceUid}");
        } catch (Exception $e) {
            Log::warning('Telemetry processing error: '.$e->getMessage());
        }
    }

    protected function handleStatus(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! is_array($data)) {
                return;
            }

            $deviceUid = $data['device_id'] ?? $this->extractDeviceUid($topic);
            if (! $deviceUid) {
                return;
            }

            $device = Device::where('device_uid', $deviceUid)->firstOrFail();

            $status = strtolower($data['status'] ?? 'online');

            $device->update([
                'status' => $status,
                'ip_address' => $data['ip_address'] ?? $device->ip_address,
                'mac_address' => $data['mac_address'] ?? $device->mac_address,
                'wifi_rssi' => isset($data['wifi_rssi']) ? (int) $data['wifi_rssi'] : $device->wifi_rssi,
                'firmware_version' => $data['firmware_version'] ?? $device->firmware_version,
                'last_seen_at' => now(),
            ]);

            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'STATUS_UPDATE',
                'title' => 'Status Perangkat: '.ucfirst($status),
                'description' => "Status koneksi perangkat diperbarui menjadi {$status}",
            ]);

            $this->line("<comment>[Status]</comment> Device {$deviceUid} status: {$status}");
        } catch (Exception $e) {
            Log::warning('Status processing error: '.$e->getMessage());
        }
    }

    protected function handleAlert(string $topic, string $message): void
    {
        try {
            $data = json_decode($message, true);
            if (! is_array($data)) {
                return;
            }

            $deviceUid = $data['device_id'] ?? $this->extractDeviceUid($topic);
            if (! $deviceUid) {
                return;
            }

            $device = Device::where('device_uid', $deviceUid)->firstOrFail();

            $channelId = null;
            if (isset($data['socket_number'])) {
                $channel = SocketChannel::where('device_id', $device->id)
                    ->where('channel_number', (int) $data['socket_number'])
                    ->first();
                $channelId = $channel?->id;
            }

            DeviceAlert::create([
                'device_id' => $device->id,
                'socket_channel_id' => $channelId,
                'alert_type' => $data['alert_type'] ?? 'EMERGENCY_ALERT',
                'trigger_value' => (float) ($data['value'] ?? 0),
                'threshold_value' => (float) ($data['threshold'] ?? 0),
                'action_taken' => $data['action_taken'] ?? 'AUTO_CUTOFF',
            ]);

            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'ALERT',
                'title' => 'Peringatan Keamanan: '.($data['alert_type'] ?? 'Bahaya'),
                'description' => ($data['action_taken'] ?? 'Tindakan otomatis diambil').' dengan nilai '.($data['value'] ?? 0),
            ]);

            $this->line("<error>[Alert]</error> Device {$deviceUid} alert: ".($data['alert_type'] ?? ''));
        } catch (Exception $e) {
            Log::warning('Alert processing error: '.$e->getMessage());
        }
    }
}
