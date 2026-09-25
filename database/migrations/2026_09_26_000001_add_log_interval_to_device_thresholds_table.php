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
        if (!Schema::hasColumn('device_thresholds', 'log_interval')) {
            Schema::table('device_thresholds', function (Blueprint $table) {
                $table->unsignedInteger('log_interval')->default(10)->after('kwh_rate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('device_thresholds', 'log_interval')) {
            Schema::table('device_thresholds', function (Blueprint $table) {
                $table->dropColumn('log_interval');
            });
        }
    }
};
