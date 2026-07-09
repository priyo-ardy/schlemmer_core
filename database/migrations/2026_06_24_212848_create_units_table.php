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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('revision')->default(0);
            $table->foreignId('category_id')->nullable()->constrained('unit_categories')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('symbol', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('conversion_factor', 20, 10)->nullable();
            $table->decimal('conversion_offset', 20, 10)->default(0);
            $table->tinyInteger('decimal_places')->unsigned()->default(3);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->restrictOnDelete(); // PERBAIKAN: Ditambahkan agar konsisten dengan uom_categories jika pakai softDeletes

            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_active');
            $table->index(['category_id', 'is_base_unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('units');
        Schema::enableForeignKeyConstraints();
    }
};
