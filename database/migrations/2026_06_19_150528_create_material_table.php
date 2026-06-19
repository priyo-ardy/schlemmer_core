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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('part_name', 150);
            $table->integer('revision')->default(0);
            $table->string('material_type')->nullable();
            $table->boolean('is_safety_part')->default(false);
            $table->string('warehouse_code')->nullable();
            $table->string('rack_code')->nullable();
            $table->string('bin_code')->nullable();
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->string('unit_of_measure')->default('pcs');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('material_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('event_type');
            $table->text('change_reason');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->integer('revision')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('material_logs');
        Schema::dropIfExists('materials');

        Schema::enableForeignKeyConstraints();
    }
};
