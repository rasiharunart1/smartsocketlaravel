<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceThreshold;
use App\Models\EnvironmentalLog;use App\Models\SensorLog;use App\Models\SocketChannel;
use App\Models\TelemetryLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@smartsocket.com'],
            [
                'name' => 'John Wick',
                'password' => Hash::make('password'),
            ]
        );

        $device = Device::firstOrCreate(
            ['device_uid' => env('DEFAULT_DEVICE_UID', 'ESP32_SOCKET_01')],
            [
                'user_id' => $user->id,
                'name' => 'Monitoring Smart Socket',
                'status' => 'online',
                'ip_address' => '192.168.1.144',
                'mac_address' => '4E:A1:02:FF:88',
                'wifi_rssi' => -42,
                'firmware_version' => '2.1.4',
                'last_seen_at' => now(),
            ]
        );

        $socket1 = SocketChannel::firstOrCreate(
            ['device_id' => $device->id, 'channel_number' => 1],
            [
                'name' => 'Socket 1',
                'pzem_identifier' => 'PZEM_01',
                'is_active' => true,
                'status' => 'normal',
            ]
        );

        $socket2 = SocketChannel::firstOrCreate(
            ['device_id' => $device->id, 'channel_number' => 2],
            [
                'name' => 'Socket 2',
                'pzem_identifier' => 'PZEM_02',
                'is_active' => true,
                'status' => 'normal',
            ]
        );

        DeviceThreshold::firstOrCreate(
            ['device_id' => $device->id],
            [
                'max_voltage' => 245.00,
                'max_current' => 15.50,
                'max_temperature' => 65.00,
                'max_smoke_ppm' => 995.00,
            ]
        );

        // Seed initial telemetry logs if empty
        if (TelemetryLog::where('socket_channel_id', $socket1->id)->count() === 0) {
            $now = Carbon::now();
            for ($i = 10; $i >= 0; $i--) {
                $time = $now->copy()->subMinutes($i * 5);

                TelemetryLog::create([
                    'socket_channel_id' => $socket1->id,
                    'voltage' => round(220.5 + (rand(-15, 15) / 10), 2),
                    'current' => round(0.45 + (rand(-5, 5) / 100), 3),
                    'power' => round(99.2 + rand(-5, 5), 2),
                    'energy' => round(12.45 + (0.01 * (10 - $i)), 3),
                    'frequency' => 50.00,
                    'power_factor' => 0.98,
                    'recorded_at' => $time,
                ]);

                TelemetryLog::create([
                    'socket_channel_id' => $socket2->id,
                    'voltage' => round(221.0 + (rand(-15, 15) / 10), 2),
                    'current' => round(0.32 + (rand(-5, 5) / 100), 3),
                    'power' => round(70.7 + rand(-5, 5), 2),
                    'energy' => round(8.12 + (0.008 * (10 - $i)), 3),
                    'frequency' => 50.00,
                    'power_factor' => 0.97,
                    'recorded_at' => $time,
                ]);

                EnvironmentalLog::create([
                    'device_id' => $device->id,
                    'temperature' => round(34.2 + (rand(-10, 10) / 10), 2),
                    'smoke_ppm' => round(930 + rand(-20, 20), 2),
                    'recorded_at' => $time,
                ]);
            }
        }

        // Seed unified sensor logs if empty
        if (SensorLog::where('device_id', $device->id)->count() === 0) {
            $now = Carbon::now();
            for ($i = 24; $i >= 0; $i--) {
                $time = $now->copy()->subMinutes($i * 10);
                $v1 = round(220.5 + (rand(-15, 15) / 10), 2);
                $c1 = round(0.45 + (rand(-5, 5) / 100), 3);
                $p1 = round(99.2 + rand(-5, 5), 2);
                $e1 = round(12.45 + (0.01 * (24 - $i)), 3);

                $v2 = round(221.0 + (rand(-15, 15) / 10), 2);
                $c2 = round(0.32 + (rand(-5, 5) / 100), 3);
                $p2 = round(70.7 + rand(-5, 5), 2);
                $e2 = round(8.12 + (0.008 * (24 - $i)), 3);

                $temp = round(34.2 + (rand(-10, 10) / 10), 2);
                $smoke = round(930 + rand(-20, 20), 2);

                SensorLog::create([
                    'device_id' => $device->id,
                    'voltage_1' => $v1,
                    'current_1' => $c1,
                    'power_1' => $p1,
                    'energy_1' => $e1,
                    'frequency_1' => 50.00,
                    'power_factor_1' => 0.98,
                    'voltage_2' => $v2,
                    'current_2' => $c2,
                    'power_2' => $p2,
                    'energy_2' => $e2,
                    'frequency_2' => 50.00,
                    'power_factor_2' => 0.97,
                    'temperature' => $temp,
                    'smoke_ppm' => $smoke,
                    'recorded_at' => $time,
                ]);
            }
        }

        // Seed activities if empty
        if (ActivityLog::where('device_id', $device->id)->count() === 0) {
            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'SWITCH_ON',
                'title' => 'Socket Dinyalakan',
                'description' => 'Pengaktifan manual melalui web panel',
                'created_at' => now()->subMinutes(30),
            ]);

            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'SCHEDULE',
                'title' => 'Penjadwalan: Daya Mati',
                'description' => 'Pengaturan penghematan energi malam hari',
                'created_at' => now()->subHours(10),
            ]);

            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'SURGE_WARNING',
                'title' => 'Lonjakan Tegangan Terdeteksi',
                'description' => 'Fluktuasi hingga 245 V berhasil ditangani',
                'created_at' => now()->subHours(20),
            ]);

            ActivityLog::create([
                'device_id' => $device->id,
                'event_type' => 'SYSTEM_UPDATE',
                'title' => 'Sistem Diperbarui',
                'description' => 'Sistem diperbarui ke versi 2.1.4',
                'created_at' => now()->subDays(2),
            ]);
        }
    }
}
