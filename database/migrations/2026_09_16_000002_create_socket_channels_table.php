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
        Schema::create('socket_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedTinyInteger('channel_number'); // 1 or 2
            $table->string('name'); // e.g. "Socket 1"
            $table->string('pzem_identifier'); // e.g. "PZEM_01"
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('normal'); // normal, warning, cutoff
            $table->timestamps();

            $table->unique(['device_id', 'channel_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socket_channels');
    }
};
