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
            $table->uuid('uuid')->unique();
            $table->integer('revision')->default(0);
            $table->enum('category', ['raw_material', 'purchased_parts', 'chemical_additive', 'tooling_consumable', 'packaging', 'sfg', 'fg']);
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('specification', 255);
            $table->string('customer_part_name', 255);
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('grade', 150)->nullable();
            $table->decimal('density', 8, 4)->default(0)->comment('Density (g/cm3) - Penting untuk perhitungan volume injeksi & shot weight');
            $table->decimal('melt_flow_index', 8, 4)->default(0)->comment('MFI (g/10 min) - Menentukan viskositas flow resin di mesin molding');
            $table->string('color', 50)->nullable()->comment('warna material/part');
            $table->string('shrinkage_rate', 50)->nullable()->comment('Nilai penyusutan material (%) untuk akurasi dimensi molding');
            $table->decimal('gross_weight', 8, 4)->default(0);
            $table->decimal('net_weight', 8, 4)->default(0);
            $table->decimal('sprue_weight', 8, 4)->default(0);
            $table->boolean('has_rohs')->default(false)->comment('Status kepatuhan Restriction of Hazardous Substances');
            $table->string('imds_number', 50)->nullable()->comment('Nomor registrasi International Material Data System');
            $table->string('msds_doc_path', 150)->nullable()->comment('Pathway file PDF Material Safety Data Sheet');
            $table->enum('risk_profile', ['low', 'medium', 'high'])->default('low')->comment('Berdasarkan kepatuhan spesifikasi material');
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('materials');
        Schema::enableForeignKeyConstraints();
    }
};
