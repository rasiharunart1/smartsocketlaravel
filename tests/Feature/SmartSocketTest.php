<?php

namespace Tests\Feature;

use App\Actions\CreateUserDevice;
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

    private function createUserWithDevice(): User
    {
        $user = User::factory()->create();
        app(CreateUserDevice::class)->handle($user);

        return $user;
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
        $user = $this->createUserWithDevice();

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        $this->actingAs($user)->get('/analytics')->assertStatus(200);
        $this->actingAs($user)->get('/history')->assertStatus(200);
        $this->actingAs($user)->get('/settings')->assertStatus(200);
        $this->actingAs($user)->get('/about')->assertStatus(200);
    }

    public function test_socket_toggle_endpoint(): void
    {
        $user = $this->createUserWithDevice();
        $device = $user->devices()->firstOrFail();
        $socket = $device->socketChannels()->where('channel_number', 1)->firstOrFail();

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
        $user = $this->createUserWithDevice();

        $response = $this->actingAs($user)->getJson('/api/device/telemetry');

        $response->assertStatus(200)
            ->assertJson([
                'device' => ['status' => 'offline', 'wifi_rssi' => 0],
                'socket_1' => [
                    'is_active' => false,
                    'voltage' => 0,
                    'current' => 0,
                    'power' => 0,
                    'energy' => 0,
                    'frequency' => 0,
                    'power_factor' => 0,
                ],
                'socket_2' => [
                    'is_active' => false,
                    'voltage' => 0,
                    'current' => 0,
                    'power' => 0,
                    'energy' => 0,
                    'frequency' => 0,
                    'power_factor' => 0,
                ],
                'environmental' => ['temperature' => 0, 'smoke_ppm' => 0],
                'total_power' => 0,
                'total_energy' => 0,
            ])
            ->assertJsonStructure([
                'device' => ['status', 'wifi_rssi', 'ip_address', 'mac_address'],
                'socket_1' => ['is_active', 'voltage', 'current', 'power', 'energy'],
                'socket_2' => ['is_active', 'voltage', 'current', 'power', 'energy'],
                'environmental' => ['temperature', 'smoke_ppm'],
            ]);
    }

    public function test_settings_update(): void
    {
        $user = $this->createUserWithDevice();
        $device = $user->devices()->firstOrFail();

        $response = $this->actingAs($user)->post('/settings', [
            'max_voltage' => 248.0,
            'max_current' => 16.0,
            'max_temperature' => 70.0,
            'max_smoke_ppm' => 950.0,
            'kwh_rate' => 1444.70,
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
        $user = $this->createUserWithDevice();

        $response = $this->actingAs($user)->get('/history/export');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'text/csv'));
    }

    public function test_user_can_update_own_mqtt_credentials(): void
    {
        $user = $this->createUserWithDevice();
        $device = $user->devices()->firstOrFail();

        $response = $this->actingAs($user)->put('/settings/mqtt', [
            'mqtt_host' => 'broker.example.com',
            'mqtt_port' => 8883,
            'mqtt_tls' => true,
            'mqtt_username' => 'mqtt-user',
            'mqtt_password' => 'super-secret',
            'mqtt_client_id' => 'smart-socket-client',
        ]);

        $response->assertRedirect(route('settings'));

        $device->refresh();
        $this->assertSame('broker.example.com', $device->mqtt_host);
        $this->assertSame(8883, $device->mqtt_port);
        $this->assertTrue($device->mqtt_tls);
        $this->assertSame('mqtt-user', $device->mqtt_username);
        $this->assertSame('super-secret', $device->mqtt_password);
        $this->assertNotSame('super-secret', $device->getRawOriginal('mqtt_password'));
    }

    public function test_user_cannot_read_or_control_another_users_device(): void
    {
        $firstUser = $this->createUserWithDevice();
        $secondUser = $this->createUserWithDevice();
        $firstDevice = $firstUser->devices()->firstOrFail();
        $secondSocket = $secondUser->devices()->firstOrFail()->socketChannels()->where('channel_number', 1)->firstOrFail();

        $this->actingAs($firstUser)->postJson('/api/socket/toggle', [
            'socket_number' => 1,
            'state' => true,
        ])->assertOk();

        $this->assertTrue($firstDevice->socketChannels()->where('channel_number', 1)->firstOrFail()->fresh()->is_active);
        $this->assertFalse($secondSocket->fresh()->is_active);
    }
}
