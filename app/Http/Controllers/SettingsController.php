<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceThreshold;
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

    public function index(): View
    {
        $deviceUid = config('mqtt.default_device_uid', 'ESP32_SOCKET_01');
        $device = Device::where('device_uid', $deviceUid)->first() ?? Device::first();

        $threshold = DeviceThreshold::firstOrCreate(
            ['device_id' => $device?->id],
            [
                'max_voltage' => 245.0,
                'max_current' => 15.5,
                'max_temperature' => 65.0,
                'max_smoke_ppm' => 995.0,
                'kwh_rate' => 1444.70,
            ]
        );

        return view('settings', compact('device', 'threshold'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_voltage' => 'required|numeric|min:150|max:300',
            'max_current' => 'required|numeric|min:0.5|max:30',
            'max_temperature' => 'required|numeric|min:30|max:120',
            'max_smoke_ppm' => 'required|numeric|min:100|max:5000',
            'kwh_rate' => 'required|numeric|min:0|max:100000',
        ]);

        $deviceUid = config('mqtt.default_device_uid', 'ESP32_SOCKET_01');
        $device = Device::where('device_uid', $deviceUid)->firstOrFail();

        $threshold = DeviceThreshold::updateOrCreate(
            ['device_id' => $device->id],
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
            $mqttDelivered = $this->mqttService->publishThreshold($device->device_uid, [
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
            'description' => "Batas baru: {$validated['max_voltage']}V, {$validated['max_current']}A, {$validated['max_temperature']}°C, {$validated['max_smoke_ppm']}ppm, tarif Rp " . number_format((float) $validated['kwh_rate'], 2, ',', '.') . "/kWh" . ($mqttDelivered ? ' (Tersinkron ke broker MQTT)' : ''),
        ]);

        $statusMsg = 'Ambang batas keamanan berhasil disimpan!' . ($mqttDelivered ? ' Pengaturan disinkronkan ke ESP32 via HiveMQ.' : '');

        return redirect()->route('settings')->with('status', $statusMsg);
    }
}
