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
        Schema::create('pfmea', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->date('date');
            $table->string('department_id');
            $table->integer('version')->default(0);
            $table->enum('scope', ['prototype', 'pre_launch', 'containment_epc', 'mass_production']);
            $table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
            $table->text('process_responsibility')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pfmea_core_teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pfmea_id')->constrained('pfmea')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['pfmea_id', 'user_id']);
        });

        Schema::create('pfmea_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pfmea_id')->constrained('pfmea')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->foreignId('process_id')->constrained('process_functions')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pfmea_details');
        Schema::dropIfExists('pfmea_core_teams');
        Schema::dropIfExists('pfmea');
        Schema::enableForeignKeyConstraints();
    }
};
