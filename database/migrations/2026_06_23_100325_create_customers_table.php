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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('alias', 150)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->enum('tier_level', ['oem', 'tier-1', 'tier-2', 'aftermarket'])->default('tier-1');
            $table->string('csr_reference_doc', 150)->nullable();
            $table->enum('risk_profile', ['low', 'medium', 'high'])->default('medium');
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index('tier_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
