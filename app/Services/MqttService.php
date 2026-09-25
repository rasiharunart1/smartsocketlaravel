<?php

namespace App\Services;

use App\Models\Device;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    protected int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('mqtt.timeout', 10);
    }

    public function getClient(?string $clientId = null, ?Device $device = null): MqttClient
    {
        $host = $device?->mqtt_host ?: config('mqtt.host');
        $port = $device?->mqtt_port ?: (int) config('mqtt.port', 8883);
        $id = $clientId ?? $device?->mqtt_client_id ?? ('laravel_pub_'.uniqid());

        return new MqttClient($host, $port, $id);
    }

    public function getConnectionSettings(?Device $device = null): ConnectionSettings
    {
        $username = $device?->mqtt_username ?: config('mqtt.username', '');
        $password = $device?->mqtt_password ?: config('mqtt.password', '');
        $tls = $device && $device->mqtt_tls !== null ? (bool) $device->mqtt_tls : (bool) config('mqtt.tls', true);

        $settings = (new ConnectionSettings)
            ->setConnectTimeout($this->timeout)
            ->setKeepAliveInterval(60);

        if (! empty($username)) {
            $settings = $settings->setUsername($username);
        }

        if (! empty($password)) {
            $settings = $settings->setPassword($password);
        }

        if ($tls) {
            $settings = $settings
                ->setUseTls(true)
                ->setTlsVerifyPeer(false)
                ->setTlsVerifyPeerName(false);
        }

        return $settings;
    }

    public function publish(Device $device, string $topic, array $payload, int $qos = 1, bool $retain = false): bool
    {
        $host = $device->mqtt_host ?: config('mqtt.host');
        $port = $device->mqtt_port ?: (int) config('mqtt.port', 8883);

        if (! $host || ! $port) {
            Log::notice("MQTT publish skipped for device [{$device->device_uid}]: broker host and port are not configured.");

            return false;
        }

        try {
            $client = $this->getClient(device: $device);
            $settings = $this->getConnectionSettings($device);

            $client->connect($settings, true);
            $client->publish($topic, json_encode($payload), $qos, $retain);
            $client->disconnect();

            Log::info("MQTT Published to [{$topic}]: ".json_encode($payload));

            return true;
        } catch (Exception $e) {
            Log::error("MQTT Publish failed [{$topic}]: ".$e->getMessage());

            return false;
        }
    }

    public function publishSwitch(Device $device, int $socketNumber, bool $turnOn, ?string $requestedBy = null): bool
    {
        $channel1 = $device->socketChannels()->where('channel_number', 1)->first();
        $channel2 = $device->socketChannels()->where('channel_number', 2)->first();

        $s1State = ($socketNumber === 1) ? $turnOn : (bool) ($channel1?->is_active ?? false);
        $s2State = ($socketNumber === 2) ? $turnOn : (bool) ($channel2?->is_active ?? false);

        $topic = "smartsocket/{$device->device_uid}/command/switch";
        $payload = [
            'socket_number' => $socketNumber,
            'state' => $turnOn ? 'ON' : 'OFF',
            'requested_by' => $requestedBy ?? 'web_user',
            'timestamp' => now()->timestamp,
        ];

        $this->publish($device, $topic, $payload, 1, false);

        // Topik Sync dengan retain=true agar ESP32 yang baru boot/restart langsung menerima status relay terbaru
        $topicSync = "smartsocket/{$device->device_uid}/command/switch/sync";
        $payloadSync = [
            'socket_1' => $s1State ? 'ON' : 'OFF',
            'socket_2' => $s2State ? 'ON' : 'OFF',
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($device, $topicSync, $payloadSync, 1, true);
    }

    public function publishSwitchSync(Device $device): bool
    {
        $channel1 = $device->socketChannels()->where('channel_number', 1)->first();
        $channel2 = $device->socketChannels()->where('channel_number', 2)->first();

        $topicSync = "smartsocket/{$device->device_uid}/command/switch/sync";
        $payloadSync = [
            'socket_1' => ($channel1?->is_active ?? false) ? 'ON' : 'OFF',
            'socket_2' => ($channel2?->is_active ?? false) ? 'ON' : 'OFF',
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($device, $topicSync, $payloadSync, 1, true);
    }

    public function publishThreshold(Device $device, array $thresholds): bool
    {
        $topic = "smartsocket/{$device->device_uid}/command/threshold";
        $payload = [
            'max_voltage' => (float) ($thresholds['max_voltage'] ?? 0),
            'max_current' => (float) ($thresholds['max_current'] ?? 0),
            'max_temperature' => (float) ($thresholds['max_temperature'] ?? 0),
            'max_smoke_ppm' => (float) ($thresholds['max_smoke_ppm'] ?? 0),
            'device_interval' => (int) ($thresholds['device_interval'] ?? 5),
            'log_interval' => (int) ($thresholds['log_interval'] ?? 30),
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($device, $topic, $payload, 1, true);
    }

    public function publishReconnect(Device $device): bool
    {
        $topic = "smartsocket/{$device->device_uid}/command/reconnect";
        $payload = [
            'action' => 'RECONNECT_WIFI',
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($device, $topic, $payload, 1, false);
    }
}
