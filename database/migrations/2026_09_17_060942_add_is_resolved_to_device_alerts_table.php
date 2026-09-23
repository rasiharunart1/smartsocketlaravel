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
        if (!Schema::hasColumn('device_alerts', 'is_resolved')) {
            Schema::table('device_alerts', function (Blueprint $table) {
                $table->boolean('is_resolved')->default(false)->after('action_taken');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('device_alerts', 'is_resolved')) {
            Schema::table('device_alerts', function (Blueprint $table) {
                $table->dropColumn('is_resolved');
            });
        }
    }
};
