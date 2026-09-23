<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Str;

class CreateUserDevice
{
    public function handle(User $user): Device
    {
        do {
            $deviceUid = 'SS-'.Str::upper(Str::random(12));
        } while (Device::where('device_uid', $deviceUid)->exists());

        $device = $user->devices()->create([
            'device_uid' => $deviceUid,
            'name' => 'Smart Socket',
            'status' => 'offline',
            'wifi_rssi' => 0,
            'firmware_version' => null,
            'mqtt_port' => 0,
            'mqtt_tls' => false,
        ]);

        $device->socketChannels()->createMany([
            [
                'channel_number' => 1,
                'name' => 'Socket 1',
                'pzem_identifier' => 'PZEM_01',
                'is_active' => false,
                'status' => 'offline',
            ],
            [
                'channel_number' => 2,
                'name' => 'Socket 2',
                'pzem_identifier' => 'PZEM_02',
                'is_active' => false,
                'status' => 'offline',
            ],
        ]);

        $device->threshold()->create([
            'max_voltage' => 0,
            'max_current' => 0,
            'max_temperature' => 0,
            'max_smoke_ppm' => 0,
            'kwh_rate' => 0,
        ]);

        return $device;
    }
}
