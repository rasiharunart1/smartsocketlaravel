<?php

namespace App\Http\Controllers;

use App\Models\DeviceThreshold;
use App\Models\SensorLog;
use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->get('period', 'day');
        $device = $request->user()->devices()->firstOrFail();

        $socket1 = SocketChannel::where('device_id', $device?->id)->where('channel_number', 1)->first();
        $socket2 = SocketChannel::where('device_id', $device?->id)->where('channel_number', 2)->first();

        $startDate = match ($period) {
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            default => now()->subHours(24),
        };

        // Check if unified SensorLog has records for this device
        $hasSensorLogs = SensorLog::where('device_id', $device?->id)->exists();

        if ($hasSensorLogs) {
            $totalEnergy1 = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('energy_1') ?? 0);

            $totalEnergy2 = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('energy_2') ?? 0);

            $p1Avg = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->avg('power_1') ?? 0);

            $p2Avg = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->avg('power_2') ?? 0);

            $avgPower = round($p1Avg + $p2Avg, 1);

            $peak1 = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('power_1') ?? 0);

            $peak2 = (float) (SensorLog::where('device_id', $device?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('power_2') ?? 0);

            $peakPower = round(max($peak1, $peak2), 1);
        } else {
            $totalEnergy1 = (float) (TelemetryLog::where('socket_channel_id', $socket1?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('energy') ?? 0);

            $totalEnergy2 = (float) (TelemetryLog::where('socket_channel_id', $socket2?->id)
                ->where('recorded_at', '>=', $startDate)
                ->max('energy') ?? 0);

            $avgPower = (float) (TelemetryLog::whereIn('socket_channel_id', array_filter([$socket1?->id, $socket2?->id]))
                ->where('recorded_at', '>=', $startDate)
                ->avg('power') ?? 0);

            $peakPower = (float) (TelemetryLog::whereIn('socket_channel_id', array_filter([$socket1?->id, $socket2?->id]))
                ->where('recorded_at', '>=', $startDate)
                ->max('power') ?? 0);
        }

        // Estimated cost (dynamic tariff from settings)
        $threshold = DeviceThreshold::where('device_id', $device?->id)->first();
        $plnRate = (float) ($threshold->kwh_rate ?? 0);
        $totalKwh = $totalEnergy1 + $totalEnergy2;
        $estimatedCost = $totalKwh * $plnRate;

        // Prepare chart intervals strictly from database
        $chartData = [];
        if ($period === 'day') {
            // 6 intervals of 4 hours across the last 24 hours
            for ($i = 5; $i >= 0; $i--) {
                $startInterval = now()->subHours(($i + 1) * 4);
                $endInterval = now()->subHours($i * 4);
                $timeLabel = $endInterval->format('H:i');

                if ($hasSensorLogs) {
                    $p1 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $startInterval)
                        ->where('recorded_at', '<=', $endInterval)
                        ->avg('power_1') ?? 0;

                    $p2 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $startInterval)
                        ->where('recorded_at', '<=', $endInterval)
                        ->avg('power_2') ?? 0;
                } else {
                    $p1 = TelemetryLog::where('socket_channel_id', $socket1?->id)
                        ->where('recorded_at', '>=', $startInterval)
                        ->where('recorded_at', '<=', $endInterval)
                        ->avg('power') ?? 0;

                    $p2 = TelemetryLog::where('socket_channel_id', $socket2?->id)
                        ->where('recorded_at', '>=', $startInterval)
                        ->where('recorded_at', '<=', $endInterval)
                        ->avg('power') ?? 0;
                }

                $chartData[] = [
                    'label' => $timeLabel,
                    'socket_1' => round((float) $p1, 1),
                    'socket_2' => round((float) $p2, 1),
                ];
            }
        } elseif ($period === 'week') {
            // Day-by-day for the last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $targetDay = now()->subDays($i);

                if ($hasSensorLogs) {
                    $p1 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $targetDay->copy()->startOfDay())
                        ->where('recorded_at', '<=', $targetDay->copy()->endOfDay())
                        ->avg('power_1') ?? 0;

                    $p2 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $targetDay->copy()->startOfDay())
                        ->where('recorded_at', '<=', $targetDay->copy()->endOfDay())
                        ->avg('power_2') ?? 0;
                } else {
                    $p1 = TelemetryLog::where('socket_channel_id', $socket1?->id)
                        ->where('recorded_at', '>=', $targetDay->copy()->startOfDay())
                        ->where('recorded_at', '<=', $targetDay->copy()->endOfDay())
                        ->avg('power') ?? 0;

                    $p2 = TelemetryLog::where('socket_channel_id', $socket2?->id)
                        ->where('recorded_at', '>=', $targetDay->copy()->startOfDay())
                        ->where('recorded_at', '<=', $targetDay->copy()->endOfDay())
                        ->avg('power') ?? 0;
                }

                $chartData[] = [
                    'label' => $targetDay->format('d M'),
                    'socket_1' => round((float) $p1, 1),
                    'socket_2' => round((float) $p2, 1),
                ];
            }
        } else {
            // Month view: 10 sample points across 30 days (every 3 days)
            for ($i = 9; $i >= 0; $i--) {
                $targetStart = now()->subDays(($i + 1) * 3)->startOfDay();
                $targetEnd = now()->subDays($i * 3)->endOfDay();

                if ($hasSensorLogs) {
                    $p1 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $targetStart)
                        ->where('recorded_at', '<=', $targetEnd)
                        ->avg('power_1') ?? 0;

                    $p2 = SensorLog::where('device_id', $device?->id)
                        ->where('recorded_at', '>=', $targetStart)
                        ->where('recorded_at', '<=', $targetEnd)
                        ->avg('power_2') ?? 0;
                } else {
                    $p1 = TelemetryLog::where('socket_channel_id', $socket1?->id)
                        ->where('recorded_at', '>=', $targetStart)
                        ->where('recorded_at', '<=', $targetEnd)
                        ->avg('power') ?? 0;

                    $p2 = TelemetryLog::where('socket_channel_id', $socket2?->id)
                        ->where('recorded_at', '>=', $targetStart)
                        ->where('recorded_at', '<=', $targetEnd)
                        ->avg('power') ?? 0;
                }

                $chartData[] = [
                    'label' => $targetEnd->format('d M'),
                    'socket_1' => round((float) $p1, 1),
                    'socket_2' => round((float) $p2, 1),
                ];
            }
        }

        // Determine dynamic chart scale from database peak
        $maxDataVal = 0;
        foreach ($chartData as $d) {
            $maxDataVal = max($maxDataVal, $d['socket_1'], $d['socket_2']);
        }
        $maxVal = max($maxDataVal, $peakPower, 10);

        if ($maxVal > 1000) {
            $maxChartPower = ceil($maxVal / 500) * 500;
        } elseif ($maxVal > 500) {
            $maxChartPower = ceil($maxVal / 200) * 200;
        } elseif ($maxVal > 200) {
            $maxChartPower = ceil($maxVal / 100) * 100;
        } elseif ($maxVal > 100) {
            $maxChartPower = ceil($maxVal / 50) * 50;
        } else {
            $maxChartPower = max(100, ceil($maxVal / 20) * 20);
        }

        return view('analytics', compact(
            'period',
            'device',
            'socket1',
            'socket2',
            'totalEnergy1',
            'totalEnergy2',
            'totalKwh',
            'avgPower',
            'peakPower',
            'estimatedCost',
            'plnRate',
            'chartData',
            'maxChartPower'
        ));
    }
}
