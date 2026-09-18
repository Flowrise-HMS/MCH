<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Patient history views order by date across all measurement types; the existing
     * (patient_id, type, date) index cannot serve that sort.
     */
    public function up(): void
    {
        Schema::table('growth_measurements', function (Blueprint $table) {
            $table->index(['patient_id', 'date'], 'growth_measurements_patient_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('growth_measurements', function (Blueprint $table) {
            $table->dropIndex('growth_measurements_patient_date_index');
        });
    }
};
