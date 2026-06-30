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
            $table->integer('severity')->default(0)->after('potential_cause_of_failure');
            $table->string('classification')->default(null)->after('potential_cause_of_failure');
            $table->integer('occurrence')->default(0)->after('classification');
            $table->integer('detection')->default(0)->after('occurrence');
            $table->integer('rpn')->default(0)->after('detection');
            $table->string('recommended_action')->default(null)->after('rpn');
        });

        Schema::table('process_revision_details', function (Blueprint $table) {
            $table->integer('severity')->default(0)->after('potential_cause_of_failure');
            $table->string('classification')->default(null)->after('potential_cause_of_failure');
            $table->integer('occurrence')->default(0)->after('classification');
            $table->integer('detection')->default(0)->after('occurrence');
            $table->integer('rpn')->default(0)->after('detection');
            $table->string('recommended_action')->default(null)->after('rpn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_function_details', function (Blueprint $table) {
            $table->dropColumn(['severity', 'classification', 'occurrence', 'detection', 'rpn', 'recommended_action']);
        });

        Schema::table('process_revision_details', function (Blueprint $table) {
            $table->dropColumn(['severity', 'classification', 'occurrence', 'detection', 'rpn', 'recommended_action']);
        });
    }
};
