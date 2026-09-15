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
        Schema::create('approval_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('transaction');
            $table->string('event', 25)->nullable();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->boolean('approval_status')->nullable();
            $table->text('remark')->nullable()->default(null);
            $table->dateTime('approved_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_transactions');
    }
};
