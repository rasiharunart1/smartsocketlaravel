<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->integer('wifi_rssi')->nullable()->default(0)->change();
            $table->string('firmware_version')->nullable()->default(null)->change();
            $table->string('mqtt_host')->nullable()->after('last_seen_at');
            $table->unsignedSmallInteger('mqtt_port')->nullable()->default(0)->after('mqtt_host');
            $table->boolean('mqtt_tls')->default(false)->after('mqtt_port');
            $table->text('mqtt_username')->nullable()->after('mqtt_tls');
            $table->text('mqtt_password')->nullable()->after('mqtt_username');
            $table->string('mqtt_client_id')->nullable()->after('mqtt_password');
        });

        Schema::table('device_thresholds', function (Blueprint $table) {
            $table->decimal('max_voltage', 6, 2)->default(0)->change();
            $table->decimal('max_current', 6, 2)->default(0)->change();
            $table->decimal('max_temperature', 5, 2)->default(0)->change();
            $table->decimal('max_smoke_ppm', 7, 2)->default(0)->change();
            $table->decimal('kwh_rate', 10, 2)->default(0)->change();
        });

        Schema::table('socket_channels', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->change();
            $table->string('status')->default('offline')->change();
        });

        Schema::table('telemetry_logs', function (Blueprint $table) {
            $table->decimal('frequency', 5, 2)->default(0)->change();
            $table->decimal('power_factor', 4, 2)->nullable()->default(0)->change();
        });

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->decimal('frequency_1', 5, 2)->default(0)->change();
            $table->decimal('power_factor_1', 4, 2)->nullable()->default(0)->change();
            $table->decimal('frequency_2', 5, 2)->default(0)->change();
            $table->decimal('power_factor_2', 4, 2)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->decimal('frequency_1', 5, 2)->default(50)->change();
            $table->decimal('power_factor_1', 4, 2)->nullable()->default(1)->change();
            $table->decimal('frequency_2', 5, 2)->default(50)->change();
            $table->decimal('power_factor_2', 4, 2)->nullable()->default(1)->change();
        });

        Schema::table('telemetry_logs', function (Blueprint $table) {
            $table->decimal('frequency', 5, 2)->default(50)->change();
            $table->decimal('power_factor', 4, 2)->nullable()->default(1)->change();
        });

        Schema::table('socket_channels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->change();
            $table->string('status')->default('normal')->change();
        });

        Schema::table('device_thresholds', function (Blueprint $table) {
            $table->decimal('max_voltage', 6, 2)->default(245)->change();
            $table->decimal('max_current', 6, 2)->default(15.5)->change();
            $table->decimal('max_temperature', 5, 2)->default(65)->change();
            $table->decimal('max_smoke_ppm', 7, 2)->default(995)->change();
            $table->decimal('kwh_rate', 10, 2)->default(1444.70)->change();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'mqtt_host',
                'mqtt_port',
                'mqtt_tls',
                'mqtt_username',
                'mqtt_password',
                'mqtt_client_id',
            ]);
            $table->integer('wifi_rssi')->nullable()->default(null)->change();
            $table->string('firmware_version')->default('2.1.4')->change();
        });
    }
};
