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
        Schema::create('approval_setups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('revision')->default(0);
            $table->string('module', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_setup_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('header_id')->constrained('approval_setups')->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('approval_setup_details');
        Schema::dropIfExists('approval_setups');
        Schema::enableForeignKeyConstraints();
    }
};
