<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immunization_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('vaccine_id')->constrained('vaccines')->restrictOnDelete();
            $table->unsignedSmallInteger('dose_sequence');
            $table->string('status'); // ImmunizationStatus enum
            $table->date('administered_date')->nullable();
            $table->string('batch_lot')->nullable();
            $table->string('site')->nullable();
            $table->string('route')->nullable();
            $table->string('reason')->nullable(); // for DECLINED / CONTRAINDICATED
            $table->uuid('encounter_id')->nullable(); // optional link to Encounter
            $table->uuid('medication_id')->nullable(); // optional link to Pharmacy medication
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // No unique on (patient_id, vaccine_id, dose_sequence) —
            // status-aware uniqueness is enforced in the service layer
            // to allow DECLINED then later ADMINISTERED for the same dose.
            $table->index(['patient_id', 'vaccine_id']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_records');
    }
};
