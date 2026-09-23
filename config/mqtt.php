<?php

return [
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => (int) env('MQTT_PORT', 8883),
    'tls' => env('MQTT_TLS', true),
    'username' => env('MQTT_AUTH_USERNAME', ''),
    'password' => env('MQTT_AUTH_PASSWORD', ''),
    'client_id' => env('MQTT_CLIENT_ID', 'laravel_backend_daemon_'.uniqid()),
    'timeout' => (int) env('MQTT_TIMEOUT', 10),
    'default_device_uid' => env('DEFAULT_DEVICE_UID', 'ESP32_SOCKET_01'),
];
