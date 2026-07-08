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
        Schema::table('process_function_details', function (Blueprint $table) {
            $table->foreignId('responsibility')->nullable()->after('severity')->constrained('users')->restrictOnDelete();
            $table->date('target_completion_date')->nullable()->after('responsibility');
            $table->date('action_taken_completion_date')->nullable()->after('target_completion_date');
            $table->integer('result_severity')->default(0)->after('action_taken_completion_date');
            $table->integer('result_occurrence')->default(0)->after('result_severity');
            $table->integer('result_detection')->default(0)->after('result_occurrence');
            $table->integer('result_rpn')->default(0)->after('result_detection');
        });

        Schema::table('process_revision_details', function (Blueprint $table) {
            $table->foreignId('responsibility')->nullable()->after('severity')->constrained('users')->restrictOnDelete();
            $table->date('target_completion_date')->nullable()->after('responsibility');
            $table->date('action_taken_completion_date')->nullable()->after('target_completion_date');
            $table->integer('result_severity')->default(0)->after('action_taken_completion_date');
            $table->integer('result_occurrence')->default(0)->after('result_severity');
            $table->integer('result_detection')->default(0)->after('result_occurrence');
            $table->integer('result_rpn')->default(0)->after('result_detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_function_details', function (Blueprint $table) {
            // 1. Hapus foreign key-nya dulu (gunakan array berisi nama kolom)
            $table->dropForeign(['responsibility']);

            // 2. Baru hapus kolom-kolomnya
            $table->dropColumn([
                'responsibility',
                'target_completion_date',
                'action_taken_completion_date',
                'result_severity',
                'result_occurrence',
                'result_detection',
                'result_rpn'
            ]);
        });

        Schema::table('process_revision_details', function (Blueprint $table) {
            // 1. Hapus foreign key-nya dulu
            $table->dropForeign(['responsibility']);

            // 2. Baru hapus kolom-kolomnya
            $table->dropColumn([
                'responsibility',
                'target_completion_date',
                'action_taken_completion_date',
                'result_severity',
                'result_occurrence',
                'result_detection',
                'result_rpn'
            ]);
        });
    }
};
