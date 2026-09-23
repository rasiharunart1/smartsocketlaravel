<?php

namespace App\Http\Controllers;

use App\Models\DeviceAlert;
use App\Models\DeviceThreshold;
use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(Request $request): View
    {
        $device = $request->user()->devices()->firstOrFail();

        $sockets = $device ? SocketChannel::where('device_id', $device->id)->get() : collect();
        $totalLogs = $device ? TelemetryLog::whereIn('socket_channel_id', $sockets->pluck('id'))->count() : 0;
        $totalAlerts = $device ? DeviceAlert::where('device_id', $device->id)->count() : 0;
        $threshold = $device ? DeviceThreshold::where('device_id', $device->id)->first() : null;

        return view('about', compact('device', 'sockets', 'totalLogs', 'totalAlerts', 'threshold'));
    }
}
