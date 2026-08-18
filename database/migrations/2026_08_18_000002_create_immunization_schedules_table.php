<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immunization_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('target_population'); // child | maternal
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('immunization_schedule_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('immunization_schedule_id')->constrained('immunization_schedules')->cascadeOnDelete();
            $table->foreignUuid('vaccine_id')->constrained('vaccines')->cascadeOnDelete();
            $table->unsignedSmallInteger('dose_sequence');
            $table->unsignedSmallInteger('minimum_age_days')->default(0);
            $table->unsignedSmallInteger('maximum_age_days')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->unique(
                ['immunization_schedule_id', 'vaccine_id', 'dose_sequence'],
                'imm_sched_item_vaccine_dose_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_schedule_items');
        Schema::dropIfExists('immunization_schedules');
    }
};
