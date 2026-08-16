<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_visit_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('pregnancy_episode_id')->nullable()->constrained('pregnancy_episodes')->nullOnDelete();
            $table->unsignedTinyInteger('visit_number')->nullable();
            $table->unsignedSmallInteger('ga_weeks')->nullable();
            $table->unsignedSmallInteger('ga_days')->nullable();
            $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
            $table->string('presentation')->nullable();
            $table->string('edema')->nullable();
            $table->string('urine_protein')->nullable();
            $table->string('urine_glucose')->nullable();
            $table->json('danger_signs')->nullable();
            $table->json('drugs_given')->nullable();
            $table->boolean('referral_required')->default(false);
            $table->string('referral_destination')->nullable();
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('encounter_id');
            $table->index(['patient_id', 'visit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_visit_assessments');
    }
};
