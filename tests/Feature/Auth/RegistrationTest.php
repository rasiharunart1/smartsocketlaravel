<?php

namespace Tests\Feature\Auth;

use App\Models\Device;
use App\Models\DeviceThreshold;
use App\Models\SensorLog;
use App\Models\SocketChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $device = Device::whereHas('user', fn ($query) => $query->where('email', 'test@example.com'))->firstOrFail();

        $this->assertSame('offline', $device->status);
        $this->assertSame(0, $device->wifi_rssi);
        $this->assertNull($device->ip_address);
        $this->assertNull($device->mac_address);
        $this->assertNull($device->firmware_version);
        $this->assertNull($device->mqtt_host);
        $this->assertSame(0, $device->mqtt_port);

        $this->assertDatabaseCount('socket_channels', 2);
        $this->assertSame(2, SocketChannel::whereBelongsTo($device)->where('is_active', false)->count());

        $threshold = DeviceThreshold::whereBelongsTo($device)->firstOrFail();
        $this->assertSame('0.00', $threshold->max_voltage);
        $this->assertSame('0.00', $threshold->max_current);
        $this->assertSame('0.00', $threshold->max_temperature);
        $this->assertSame('0.00', $threshold->max_smoke_ppm);
        $this->assertSame('0.00', $threshold->kwh_rate);
        $this->assertFalse(SensorLog::whereBelongsTo($device)->exists());
    }
}
