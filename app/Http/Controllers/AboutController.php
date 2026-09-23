<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\DeviceThreshold;
use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $deviceUid = config('mqtt.default_device_uid', 'ESP32_SOCKET_01');
        $device = Device::where('device_uid', $deviceUid)->first() ?? Device::first();

        $sockets = $device ? SocketChannel::where('device_id', $device->id)->get() : collect();
        $totalLogs = $device ? TelemetryLog::whereIn('socket_channel_id', $sockets->pluck('id'))->count() : 0;
        $totalAlerts = $device ? DeviceAlert::where('device_id', $device->id)->count() : 0;
        $threshold = $device ? DeviceThreshold::where('device_id', $device->id)->first() : null;

        return view('about', compact('device', 'sockets', 'totalLogs', 'totalAlerts', 'threshold'));
    }
}
