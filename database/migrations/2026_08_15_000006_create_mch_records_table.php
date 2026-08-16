<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->index(['owner_type', 'owner_id']);
        });

        // MariaDB has no partial unique indexes. Emulate "one ACTIVE book per
        // owner per branch" with a stored generated column that is NULL for
        // non-active rows (MySQL/MariaDB unique indexes allow multiple NULLs).
        Schema::table('mch_records', function (Blueprint $table) {
            $table->string('active_owner_key')->nullable()->storedAs(
                "CASE WHEN status = 'active' THEN CONCAT(owner_type, ':', owner_id, ':', branch_id) ELSE NULL END",
            );
            $table->unique('active_owner_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mch_records');
    }
};
