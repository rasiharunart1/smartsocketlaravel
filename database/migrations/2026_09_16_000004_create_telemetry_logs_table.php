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
        Schema::create('telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socket_channel_id')->constrained('socket_channels')->cascadeOnDelete();
            $table->decimal('voltage', 6, 2)->default(0);
            $table->decimal('current', 6, 3)->default(0);
            $table->decimal('power', 8, 2)->default(0);
            $table->decimal('energy', 10, 3)->default(0);
            $table->decimal('frequency', 5, 2)->default(50.00);
            $table->decimal('power_factor', 4, 2)->nullable()->default(1.00);
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['socket_channel_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_logs');
    }
};
