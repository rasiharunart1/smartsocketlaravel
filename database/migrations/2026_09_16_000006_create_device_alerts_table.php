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
        Schema::create('device_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('socket_channel_id')->nullable()->constrained('socket_channels')->nullOnDelete();
            $table->string('alert_type'); // OVER_VOLTAGE, OVER_CURRENT, OVER_TEMPERATURE, SMOKE_DETECTED
            $table->decimal('trigger_value', 8, 2);
            $table->decimal('threshold_value', 8, 2);
            $table->string('action_taken')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_alerts');
    }
};
