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
            $table->uuid('uuid')->unique()->after('id');
            $table->string('previous_problem', 255)->nullable()->after('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_function_details', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'previous_problem']);
        });
    }
};
