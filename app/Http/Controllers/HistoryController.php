<?php

namespace App\Http\Controllers;

use App\Models\SensorLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $device = $request->user()->devices()->firstOrFail();

        $socketFilter = $request->get('socket');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = SensorLog::where('device_id', $device?->id)
            ->latest('recorded_at');

        if ($startDate) {
            $query->where('recorded_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('recorded_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $logs = $query->paginate(15)->withQueryString();

        // Latest environmental temperature & smoke for quick reference
        $latestLog = SensorLog::where('device_id', $device?->id)->latest('recorded_at')->first();
        $currentTemp = $latestLog ? (float) $latestLog->temperature : 0;
        $currentSmoke = $latestLog ? (float) $latestLog->smoke_ppm : 0;
        $threshold = $device->threshold;

        return view('history', compact('logs', 'device', 'socketFilter', 'startDate', 'endDate', 'currentTemp', 'currentSmoke', 'threshold'));
    }

    public function export(Request $request): StreamedResponse
    {
        $device = $request->user()->devices()->firstOrFail();

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = SensorLog::where('device_id', $device?->id)
            ->latest('recorded_at');

        if ($startDate) {
            $query->where('recorded_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('recorded_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $fileName = 'smart_socket_unified_logs_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Timestamp',
                'Tegangan S1 (V)',
                'Arus S1 (A)',
                'Daya S1 (W)',
                'Energi S1 (kWh)',
                'Frekuensi S1 (Hz)',
                'PF S1',
                'Tegangan S2 (V)',
                'Arus S2 (A)',
                'Daya S2 (W)',
                'Energi S2 (kWh)',
                'Frekuensi S2 (Hz)',
                'PF S2',
                'Suhu Enclosure (C)',
                'Asap MQ-2 (ppm)',
                'Total Daya (W)',
                'Total Energi (kWh)',
            ]);

            $query->chunk(200, function ($records) use ($file) {
                foreach ($records as $log) {
                    fputcsv($file, [
                        $log->recorded_at->format('Y-m-d H:i:s'),
                        $log->voltage_1,
                        $log->current_1,
                        $log->power_1,
                        $log->energy_1,
                        $log->frequency_1,
                        $log->power_factor_1,
                        $log->voltage_2,
                        $log->current_2,
                        $log->power_2,
                        $log->energy_2,
                        $log->frequency_2,
                        $log->power_factor_2,
                        $log->temperature,
                        $log->smoke_ppm,
                        round($log->power_1 + $log->power_2, 1),
                        round($log->energy_1 + $log->energy_2, 3),
                    ]);
                }
            });

            fclose($file);
        }, 200, $headers);
    }
}
