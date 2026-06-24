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
            // 1. Primary & Unique Identifiers
            $table->id();
            $table->uuid('uuid')->unique();

            // RELASI KE PROJECT (Sudah dihapus ->after()-nya karena posisinya udah bener di bawah uuid)
            $table->foreignId('project_id')->nullable()
                ->constrained('projects')
                ->nullOnDelete()
                ->comment('Menghubungkan part/material ke project spesifik (jika ada)');

            $table->string('code', 50)->unique()->comment('Contoh: MAT-PP-001, PART-7171 (Molded WIP), PART-7176 (Assembled FG)');
            $table->string('name', 150)->comment('Nama komersial / internal material / nama komponen');
            $table->string('alias', 100)->nullable()->comment('Nama komersial dari vendor atau trade name dari customer');

            // 2. Tipe & Klasifikasi
            $table->string('type', 50)->default('raw_material')
                ->comment('raw_material, semi_finished (WIP/Molded), finished_good (Assembled/FG), component, chemical');
            $table->string('uom', 20)->default('pcs')->comment('Unit of Measure utama, misal: kg, pcs, mtr');

            // 3. Parameter Teknis Injeksi Plastik (PFMEA Ready)
            $table->string('grade', 100)->nullable()->comment('Contoh: Grade MFI tinggi, Glass Fiber 30%, dll');
            $table->decimal('density', 8, 4)->nullable()->comment('Density (g/cm3) - Penting untuk perhitungan volume injeksi & shot weight');
            $table->decimal('melt_flow_index', 8, 2)->nullable()->comment('MFI (g/10 min) - Menentukan viskositas flow resin di mesin molding');
            $table->string('color_code', 50)->nullable()->comment('Kode warna standar manufaktur, misal: RAL 9005 Jet Black');
            $table->string('shrinkage_rate', 50)->nullable()->comment('Nilai penyusutan material (%) untuk akurasi dimensi molding');

            // 4. Kepatuhan Kualitas & Regulasi Otomotif (IATF Auditable)
            $table->boolean('has_rohs')->default(false)->comment('Status kepatuhan Restriction of Hazardous Substances');
            $table->string('imds_number', 50)->nullable()->comment('Nomor registrasi International Material Data System');
            $table->string('msds_doc_path')->nullable()->comment('Pathway file PDF Material Safety Data Sheet');
            $table->string('risk_profile', 50)->default('low')->comment('low, medium, high - Berdasarkan kepatuhan spesifikasi material');

            // 5. Versioning & Kontrol Operasional
            $table->unsignedInteger('revision')->default(1)->comment('Sistem tracking versi perubahan spesifikasi data material');
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable()->comment('Catatan tambahan terkait modifikasi spesifikasi teknik');

            // 6. Audit Trails (User Tracking)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // 7. Pengindeksan Performa
            $table->index(['type', 'is_active']);
            $table->index('code');
            $table->index('project_id');
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
