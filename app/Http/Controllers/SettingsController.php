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

        $initialThreshold = [
            'max_voltage' => 245,
            'max_current' => 15.5,
            'max_temperature' => 65,
            'max_smoke_ppm' => 995,
            'kwh_rate' => 1444.70,
            'log_interval' => 30,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('device_thresholds', 'device_interval')) {
            $initialThreshold['device_interval'] = 5;
        }

        $threshold = $device->threshold ?: $device->threshold()->create($initialThreshold);

        return view('settings', compact('device', 'threshold'));
    }

    public function update(UpdateDeviceSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $device = $request->user()->devices()->firstOrFail();

        $thresholdData = [
            'max_voltage' => (float) $validated['max_voltage'],
            'max_current' => (float) $validated['max_current'],
            'max_temperature' => (float) $validated['max_temperature'],
            'max_smoke_ppm' => (float) $validated['max_smoke_ppm'],
            'kwh_rate' => (float) $validated['kwh_rate'],
            'log_interval' => (int) ($validated['log_interval'] ?? 30),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('device_thresholds', 'device_interval')) {
            $thresholdData['device_interval'] = (int) ($validated['device_interval'] ?? 5);
        }

        $device->threshold()->updateOrCreate([], $thresholdData);

        // Publish to MQTT broker
        $mqttDelivered = false;
        try {
            $mqttDelivered = $this->mqttService->publishThreshold($device, [
                'max_voltage' => (float) $validated['max_voltage'],
                'max_current' => (float) $validated['max_current'],
                'max_temperature' => (float) $validated['max_temperature'],
                'max_smoke_ppm' => (float) $validated['max_smoke_ppm'],
                'device_interval' => (int) ($validated['device_interval'] ?? 5),
                'log_interval' => (int) ($validated['log_interval'] ?? 30),
            ]);
        } catch (Exception $e) {
            $mqttDelivered = false;
        }

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'SETTING_UPDATE',
            'title' => 'Ambang Batas & Parameter Diperbarui',
            'description' => "Batas: {$validated['max_voltage']}V, {$validated['max_current']}A, {$validated['max_temperature']}°C, {$validated['max_smoke_ppm']}ppm | Interval Device: ".($validated['device_interval'] ?? 5)."s | Interval DB: ".($validated['log_interval'] ?? 30)."s | Tarif: Rp ".number_format((float) $validated['kwh_rate'], 2, ',', '.').'/kWh'.($mqttDelivered ? ' (Tersinkron ke ESP32)' : ''),
        ]);

        $statusMsg = 'Ambang batas keamanan & parameter berhasil disimpan!'.($mqttDelivered ? ' Pengaturan disinkronkan ke ESP32 via HiveMQ.' : '');

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

    /**
     * Reset akumulasi energi (kWh) register PZEM-004T melalui perintah MQTT ke ESP32.
     */
    public function resetEnergy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'socket_number' => ['required', 'integer', 'in:0,1,2'],
        ]);

        $device = $request->user()->devices()->firstOrFail();

        $socketNumber = (int) $validated['socket_number'];

        $mqttDelivered = false;
        try {
            $mqttDelivered = $this->mqttService->publishResetEnergy($device, $socketNumber);
        } catch (Exception $e) {
            $mqttDelivered = false;
        }

        $socketLabel = match ($socketNumber) {
            1 => 'PZEM-1 (Soket 1)',
            2 => 'PZEM-2 (Soket 2)',
            default => 'PZEM-1 & PZEM-2 (Semua Soket)',
        };

        ActivityLog::create([
            'device_id' => $device->id,
            'event_type' => 'ENERGY_RESET',
            'title'      => "Reset Energi kWh {$socketLabel}",
            'description' => "Register akumulasi energi kWh {$socketLabel} direset ke 0 dari halaman Settings."
                .($mqttDelivered ? ' Perintah berhasil dikirim ke ESP32 via MQTT.' : ' (MQTT tidak terkirim – periksa koneksi broker)'),
        ]);

        $msg = $mqttDelivered
            ? "Perintah reset energi kWh {$socketLabel} berhasil dikirim ke ESP32. Nilai kWh akan segera kembali ke 0."
            : "Reset dicatat, namun MQTT tidak dapat menghubungi ESP32. Pastikan perangkat online.";

        return redirect()->route('settings')->with(
            $mqttDelivered ? 'status' : 'warning',
            $msg
        );
    }
}
