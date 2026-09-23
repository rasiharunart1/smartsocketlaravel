<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    protected string $host;
    protected int $port;
    protected bool $tls;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->host = config('mqtt.host');
        $this->port = (int) config('mqtt.port', 8883);
        $this->tls = (bool) config('mqtt.tls', true);
        $this->username = config('mqtt.username', '');
        $this->password = config('mqtt.password', '');
        $this->timeout = (int) config('mqtt.timeout', 10);
    }

    public function getClient(?string $clientId = null): MqttClient
    {
        $id = $clientId ?? ('laravel_pub_' . uniqid());
        return new MqttClient($this->host, $this->port, $id);
    }

    public function getConnectionSettings(): ConnectionSettings
    {
        $settings = (new ConnectionSettings)
            ->setConnectTimeout($this->timeout)
            ->setKeepAliveInterval(60);

        if (!empty($this->username)) {
            $settings = $settings->setUsername($this->username);
        }

        if (!empty($this->password)) {
            $settings = $settings->setPassword($this->password);
        }

        if ($this->tls) {
            $settings = $settings
                ->setUseTls(true)
                ->setTlsVerifyPeer(false)
                ->setTlsVerifyPeerName(false);
        }

        return $settings;
    }

    public function publish(string $topic, array $payload, int $qos = 1, bool $retain = false): bool
    {
        try {
            $client = $this->getClient();
            $settings = $this->getConnectionSettings();

            $client->connect($settings, true);
            $client->publish($topic, json_encode($payload), $qos, $retain);
            $client->disconnect();

            Log::info("MQTT Published to [{$topic}]: " . json_encode($payload));
            return true;
        } catch (Exception $e) {
            Log::error("MQTT Publish failed [{$topic}]: " . $e->getMessage());
            return false;
        }
    }

    public function publishSwitch(string $deviceUid, int $socketNumber, bool $turnOn, ?string $requestedBy = null): bool
    {
        $topic = "smartsocket/{$deviceUid}/command/switch";
        $payload = [
            'socket_number' => $socketNumber,
            'state' => $turnOn ? 'ON' : 'OFF',
            'requested_by' => $requestedBy ?? 'web_user',
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($topic, $payload, 1, false);
    }

    public function publishThreshold(string $deviceUid, array $thresholds): bool
    {
        $topic = "smartsocket/{$deviceUid}/command/threshold";
        $payload = [
            'max_voltage' => (float) ($thresholds['max_voltage'] ?? 245.0),
            'max_current' => (float) ($thresholds['max_current'] ?? 15.5),
            'max_temperature' => (float) ($thresholds['max_temperature'] ?? 65.0),
            'max_smoke_ppm' => (float) ($thresholds['max_smoke_ppm'] ?? 995.0),
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($topic, $payload, 1, true);
    }

    public function publishReconnect(string $deviceUid): bool
    {
        $topic = "smartsocket/{$deviceUid}/command/reconnect";
        $payload = [
            'action' => 'RECONNECT_WIFI',
            'timestamp' => now()->timestamp,
        ];

        return $this->publish($topic, $payload, 1, false);
    }
}
