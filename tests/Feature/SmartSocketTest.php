<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceThreshold;
use App\Models\SocketChannel;
use App\Models\User;
use App\Services\MqttService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmartSocketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock MqttService to prevent actual network TLS connection attempts during unit tests
        $mockMqtt = Mockery::mock(MqttService::class);
        $mockMqtt->shouldReceive('publishSwitch')->andReturn(true);
        $mockMqtt->shouldReceive('publishThreshold')->andReturn(true);
        $mockMqtt->shouldReceive('publishReconnect')->andReturn(true);
        $this->app->instance(MqttService::class, $mockMqtt);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $responseDashboard = $this->get('/dashboard');
        $responseDashboard->assertRedirect('/login');
    }

    public function test_user_can_access_dashboard_and_views(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        $this->actingAs($user)->get('/analytics')->assertStatus(200);
        $this->actingAs($user)->get('/history')->assertStatus(200);
        $this->actingAs($user)->get('/settings')->assertStatus(200);
        $this->actingAs($user)->get('/about')->assertStatus(200);
    }

    public function test_socket_toggle_endpoint(): void
    {
        $user = User::factory()->create();
        $device = Device::firstOrCreate(['device_uid' => 'ESP32_SOCKET_01'], ['name' => 'ESP32']);
        $socket = SocketChannel::firstOrCreate(
            ['device_id' => $device->id, 'channel_number' => 1],
            ['name' => 'Socket 1', 'pzem_identifier' => 'PZEM_01', 'is_active' => true]
        );

        $response = $this->actingAs($user)->postJson('/api/socket/toggle', [
            'socket_number' => 1,
            'state' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('socket_channels', [
            'id' => $socket->id,
            'is_active' => false,
        ]);
    }

    public function test_telemetry_polling_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/device/telemetry');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'device' => ['status', 'wifi_rssi', 'ip_address', 'mac_address'],
                'socket_1' => ['is_active', 'voltage', 'current', 'power', 'energy'],
                'socket_2' => ['is_active', 'voltage', 'current', 'power', 'energy'],
                'environmental' => ['temperature', 'smoke_ppm'],
            ]);
    }

    public function test_settings_update(): void
    {
        $user = User::factory()->create();
        $device = Device::firstOrCreate(['device_uid' => 'ESP32_SOCKET_01'], ['name' => 'ESP32']);

        $response = $this->actingAs($user)->post('/settings', [
            'max_voltage' => 248.0,
            'max_current' => 16.0,
            'max_temperature' => 70.0,
            'max_smoke_ppm' => 950.0,
        ]);

        $response->assertRedirect(route('settings'));

        $this->assertDatabaseHas('device_thresholds', [
            'device_id' => $device->id,
            'max_voltage' => 248.0,
            'max_current' => 16.0,
            'max_temperature' => 70.0,
            'max_smoke_ppm' => 950.0,
        ]);
    }

    public function test_history_csv_export(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history/export');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'text/csv'));
    }
}
