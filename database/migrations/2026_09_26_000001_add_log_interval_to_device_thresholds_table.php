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
        Schema::table('device_thresholds', function (Blueprint $table) {
            if (!Schema::hasColumn('device_thresholds', 'log_interval')) {
                $table->unsignedInteger('log_interval')->default(30)->after('kwh_rate');
            }
            if (!Schema::hasColumn('device_thresholds', 'device_interval')) {
                $table->unsignedInteger('device_interval')->default(5)->after('log_interval');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_thresholds', function (Blueprint $table) {
            if (Schema::hasColumn('device_thresholds', 'device_interval')) {
                $table->dropColumn('device_interval');
            }
            if (Schema::hasColumn('device_thresholds', 'log_interval')) {
                $table->dropColumn('log_interval');
            }
        });
    }
};
