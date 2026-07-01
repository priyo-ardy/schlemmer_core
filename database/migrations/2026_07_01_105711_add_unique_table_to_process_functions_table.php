<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('process_functions', function (Blueprint $table) {
            $table->dateTime('deleted_at')->nullable()->change();
        });

        Schema::table('process_functions', function (Blueprint $table) {
            $table->tinyInteger('is_active_unique')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['name', 'is_active_unique'], 'unique_active_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_functions', function (Blueprint $table) {
            $table->dropUnique('unique_active_name');
            $table->dropColumn('is_active_unique');
        });

        Schema::table('process_functions', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->change();
        });
    }
};
