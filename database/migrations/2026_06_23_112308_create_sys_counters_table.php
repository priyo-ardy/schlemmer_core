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
        Schema::create('sys_counters', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('prefix', 20);
            $table->string('format', 50);
            $table->integer('sequence_length')->default(5);
            $table->bigInteger('last_sequence')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_counters');
    }
};
