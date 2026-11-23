<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('growth_measurements', function (Blueprint $table) {
            // WHO age-based Z-score for the measurement's own type (WAZ/HAZ/HCZ/ACZ).
            $table->decimal('z_score', 5, 2)->nullable()->after('unit');
            $table->string('z_indicator', 8)->nullable()->after('z_score');
            // Weight-for-length/height Z-score, on weight rows with a same-day length.
            $table->decimal('whz', 5, 2)->nullable()->after('z_indicator');
        });
    }

    public function down(): void
    {
        Schema::table('growth_measurements', function (Blueprint $table) {
            $table->dropColumn(['z_score', 'z_indicator', 'whz']);
        });
    }
};
