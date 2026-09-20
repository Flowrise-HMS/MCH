<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scheduled doses used to recompute their due date from the child's date of
     * birth on every read and never showed it; storing it lets maternal (TT)
     * doses carry a due date too and makes the EPI due list sortable.
     */
    public function up(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('status');
            $table->index(['branch_id', 'status', 'due_date'], 'immunization_records_branch_status_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropIndex('immunization_records_branch_status_due_index');
            $table->dropColumn('due_date');
        });
    }
};
