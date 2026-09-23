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
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();

            // Socket 1 (PZEM-004T Unit 1)
            $table->decimal('voltage_1', 6, 2)->default(0);
            $table->decimal('current_1', 6, 3)->default(0);
            $table->decimal('power_1', 8, 2)->default(0);
            $table->decimal('energy_1', 10, 3)->default(0);
            $table->decimal('frequency_1', 5, 2)->default(50.00);
            $table->decimal('power_factor_1', 4, 2)->nullable()->default(1.00);

            // Socket 2 (PZEM-004T Unit 2)
            $table->decimal('voltage_2', 6, 2)->default(0);
            $table->decimal('current_2', 6, 3)->default(0);
            $table->decimal('power_2', 8, 2)->default(0);
            $table->decimal('energy_2', 10, 3)->default(0);
            $table->decimal('frequency_2', 5, 2)->default(50.00);
            $table->decimal('power_factor_2', 4, 2)->nullable()->default(1.00);

            // Environmental Sensors (Single Enclosure: DHT22 & MQ-2)
            $table->decimal('temperature', 5, 2)->default(0);
            $table->decimal('smoke_ppm', 7, 2)->default(0);

            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};
