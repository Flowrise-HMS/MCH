<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_visit_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('child_health_record_id')->nullable()->constrained('child_health_records')->nullOnDelete();
            $table->string('feeding')->nullable();
            $table->boolean('vitamin_a_given')->default(false);
            $table->boolean('dewormed')->default(false);
            $table->string('developmental_screen')->nullable();
            $table->boolean('referral_required')->default(false);
            $table->string('referral_destination')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('encounter_id');
            $table->index(['patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_visit_assessments');
    }
};
