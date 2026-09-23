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
            $table->decimal('kwh_rate', 10, 2)->default(1444.70)->after('max_smoke_ppm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_thresholds', function (Blueprint $table) {
            $table->dropColumn('kwh_rate');
        });
    }
};
