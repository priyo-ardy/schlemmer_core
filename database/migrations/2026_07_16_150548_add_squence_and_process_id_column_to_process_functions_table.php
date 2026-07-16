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
        Schema::table('process_functions', function (Blueprint $table) {
            $table->integer('sequence')->default(1)->after('uuid');
            $table->unsignedInteger('process_parent')->after('sequence');
            $table->unsignedTinyInteger('process_child')->nullable()->after('process_parent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_functions', function (Blueprint $table) {
            $table->dropColumn('sequence');
            $table->dropColumn('process_id');
        });
    }
};
