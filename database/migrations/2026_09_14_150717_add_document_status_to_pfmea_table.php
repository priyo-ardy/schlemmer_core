<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pfmea', function (Blueprint $table) {
            $table->enum('doc_status', ['draft', 'under_review', 'waiting_approval', 'revision', 'approved', 'rejected', 'cancelled'])->default('draft')->after('is_active');
            $table->date('reviewed_date')->nullable()->after('reviewed_by');
            $table->date('approved_date')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pfmea', function (Blueprint $table) {
            $table->dropColumn(['doc_status', 'reviewed_date', 'approved_date']);
        });
    }
};
