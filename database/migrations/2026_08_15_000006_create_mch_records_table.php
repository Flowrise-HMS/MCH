<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MariaDB 11.8 rejects CHAR column references in stored/indexed generated
 * columns (error 1901). The plan's partial-unique emulation via active_owner_key
 * is replaced by the application-level guard in MchBookIssuanceService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mch_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('owner');
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('serial_number');
            $table->string('unit');
            $table->date('issue_date');
            $table->string('status')->default('active');
            $table->foreignUuid('replaced_by')->nullable()->constrained('mch_records')->nullOnDelete();
            $table->boolean('data_consented')->default(false);
            $table->dateTime('consented_at')->nullable();
            $table->foreignId('consented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mch_records');
    }
};
