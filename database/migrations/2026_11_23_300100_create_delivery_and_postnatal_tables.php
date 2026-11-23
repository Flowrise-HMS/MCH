<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MCH-4: delivery (labour outcome) records, one row per baby, and postnatal
 * (PNC) visit assessments. Child health records link back to the delivery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mch_delivery_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pregnancy_episode_id')->constrained('pregnancy_episodes')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->dateTime('delivered_at');
            $table->string('place');
            $table->string('mode');
            $table->string('attendant_type')->nullable();
            $table->uuid('attendant_staff_id')->nullable();
            $table->unsignedTinyInteger('gestation_weeks')->nullable();
            $table->unsignedTinyInteger('number_of_babies')->default(1);
            $table->unsignedSmallInteger('blood_loss_ml')->nullable();
            $table->json('complications')->nullable();
            $table->string('maternal_outcome')->default('alive');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('pregnancy_episode_id');
            $table->index(['branch_id', 'delivered_at']);
        });

        Schema::create('mch_newborns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_record_id')->constrained('mch_delivery_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('birth_order')->default(1);
            $table->string('sex');
            $table->string('outcome');
            $table->unsignedSmallInteger('birth_weight_g')->nullable();
            $table->decimal('length_cm', 5, 1)->nullable();
            $table->decimal('head_circumference_cm', 5, 1)->nullable();
            $table->unsignedTinyInteger('apgar_1')->nullable();
            $table->unsignedTinyInteger('apgar_5')->nullable();
            $table->boolean('resuscitated')->default(false);
            $table->foreignUuid('child_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->timestamps();

            $table->unique(['delivery_record_id', 'birth_order']);
        });

        Schema::table('child_health_records', function (Blueprint $table) {
            $table->foreign('delivery_record_id')->references('id')->on('mch_delivery_records')->nullOnDelete();
        });

        Schema::create('mch_postnatal_visit_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('delivery_record_id')->nullable()->constrained('mch_delivery_records')->nullOnDelete();
            $table->string('subject')->default('mother');
            $table->string('contact');
            $table->unsignedSmallInteger('days_postpartum')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('systolic_bp')->nullable();
            $table->unsignedSmallInteger('diastolic_bp')->nullable();
            $table->string('uterus')->nullable();
            $table->string('lochia')->nullable();
            $table->string('breastfeeding')->nullable();
            $table->string('cord')->nullable();
            $table->json('danger_signs')->nullable();
            $table->boolean('family_planning_counselled')->default(false);
            $table->string('family_planning_method')->nullable();
            $table->boolean('referral_required')->default(false);
            $table->string('referral_destination')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['encounter_id', 'subject']);
            $table->index(['patient_id', 'contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mch_postnatal_visit_assessments');

        Schema::table('child_health_records', function (Blueprint $table) {
            $table->dropForeign(['delivery_record_id']);
        });

        Schema::dropIfExists('mch_newborns');
        Schema::dropIfExists('mch_delivery_records');
    }
};
