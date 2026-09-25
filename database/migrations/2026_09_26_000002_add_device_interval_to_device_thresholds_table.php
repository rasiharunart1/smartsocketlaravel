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
        if (!Schema::hasColumn('device_thresholds', 'device_interval')) {
            Schema::table('device_thresholds', function (Blueprint $table) {
                $table->unsignedInteger('device_interval')->default(5)->after('log_interval');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('device_thresholds', 'device_interval')) {
            Schema::table('device_thresholds', function (Blueprint $table) {
                $table->dropColumn('device_interval');
            });
        }
    }
};
