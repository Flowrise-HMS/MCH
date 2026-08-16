<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pregnancy_episodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->unsignedTinyInteger('gravida')->nullable();
            $table->unsignedTinyInteger('parity')->nullable();
            $table->date('lmp')->nullable();
            $table->date('edd')->nullable();
            $table->string('edd_source')->default('lmp');
            $table->boolean('multiple_gestation')->default(false);
            $table->string('risk_level')->default('low');
            $table->boolean('risk_override')->default(false);
            $table->json('risk_factors')->nullable();
            $table->date('booking_date')->nullable();
            $table->unsignedTinyInteger('booking_ga_weeks')->nullable();
            $table->string('outcome')->default('active');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'outcome']);
            $table->index(['branch_id', 'edd']);
            $table->index(['branch_id', 'risk_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pregnancy_episodes');
    }
};
