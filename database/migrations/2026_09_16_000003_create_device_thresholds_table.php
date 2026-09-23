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
        Schema::create('device_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained('devices')->cascadeOnDelete();
            $table->decimal('max_voltage', 6, 2)->default(245.00);
            $table->decimal('max_current', 6, 2)->default(15.50);
            $table->decimal('max_temperature', 5, 2)->default(65.00);
            $table->decimal('max_smoke_ppm', 7, 2)->default(995.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_thresholds');
    }
};
