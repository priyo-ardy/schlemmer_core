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
        Schema::create('process_function', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->integer('revision')->default(1);
            $table->text('remark');
            $table->boolean('is_active')->default(true);
            $table->text('control_detection')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('process_function_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')->constrained('process_function')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->text('requirements')->nullable();
            $table->text('potential_failure_mode')->nullable();
            $table->text('potential_effect_of_failure')->nullable();
            $table->text('potential_cause_of_failure')->nullable();
            $table->text('controls_prevention')->nullable();
            $table->text('controls_detection')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_item_id')->constrained('process_function_details')->cascadeOnDelete();
            $table->string('text_before', 150);
            $table->string('text_after', 150);
            $table->integer('revision');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_function');
        Schema::dropIfExists('process_function_details');
        Schema::dropIfExists('  ');
    }
};
