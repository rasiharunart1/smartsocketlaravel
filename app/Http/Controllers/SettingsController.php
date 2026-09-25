<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeviceSettingsRequest;
use App\Http\Requests\UpdateMqttSettingsRequest;
use App\Models\ActivityLog;
use App\Services\MqttService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    protected MqttService $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function index(Request $request): View
    {
        $device = $request->user()->devices()->with('threshold')->first();

        if (! $device) {
            $device = $request->user()->devices()->create([
                'device_uid' => 'ESP32_SOCKET_01',
                'name' => 'Smart Socket',
                'status' => 'offline',
                'wifi_rssi' => 0,
            ]);
        }

        $threshold = $device->threshold ?: $device->threshold()->create([
            'max_voltage' => 245,
            'max_current' => 15.5,
            'max_temperature' => 65,
            'max_smoke_ppm' => 995,
            'kwh_rate' => 1444.70,
        ]);

        return view('settings', compact('device', 'threshold'));
    }

    public function update(UpdateDeviceSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $device = $request->user()->devices()->firstOrFail();

        $device->threshold()->updateOrCreate(
            [],
            [
                'max_voltage' => (float) $validated['max_voltage'],
                'max_current' => (float) $validated['max_current'],
                'max_temperature' => (float) $validated['max_temperature'],
                'max_smoke_ppm' => (float) $validated['max_smoke_ppm'],
                'kwh_rate' => (float) $validated['kwh_rate'],
            ]
        );

        // Publish to MQTT broker
        $mqttDelivered = false;
        try {
            $mqttDelivered = $this->mqttService->publishThreshold($device, [
                'max_voltage' => (float) $validated['max_voltage'],
                'max_current' => (float) $validated['max_current'],
                'max_temperature' => (float) $validated['max_temperature'],
                'max_smoke_ppm' => (float) $validated['max_smoke_ppm'],
            ]);
        } catch (Exception $e) {
            $mqttDelivered = false;
        }

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'SETTING_UPDATE',
            'title' => 'Ambang Batas Diperbarui',
            'description' => "Batas baru: {$validated['max_voltage']}V, {$validated['max_current']}A, {$validated['max_temperature']}°C, {$validated['max_smoke_ppm']}ppm, tarif Rp ".number_format((float) $validated['kwh_rate'], 2, ',', '.').'/kWh'.($mqttDelivered ? ' (Tersinkron ke broker MQTT)' : ''),
        ]);

        $statusMsg = 'Ambang batas keamanan berhasil disimpan!'.($mqttDelivered ? ' Pengaturan disinkronkan ke ESP32 via HiveMQ.' : '');

        return redirect()->route('settings')->with('status', $statusMsg);
    }

    public function updateMqtt(UpdateMqttSettingsRequest $request): RedirectResponse
    {
        $device = $request->user()->devices()->firstOrFail();
        $mqttSettings = $request->safe()->only([
            'mqtt_host',
            'mqtt_port',
            'mqtt_tls',
            'mqtt_username',
            'mqtt_client_id',
        ]);

        if ($request->filled('mqtt_password')) {
            $mqttSettings['mqtt_password'] = $request->string('mqtt_password')->toString();
        }

        $device->update($mqttSettings);

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'MQTT_UPDATE',
            'title' => 'Kredensial MQTT Diperbarui',
            'description' => 'Konfigurasi koneksi MQTT perangkat diperbarui dari halaman Settings.',
        ]);

        return redirect()->route('settings')->with('status', 'Kredensial MQTT berhasil disimpan.');
    }
}
