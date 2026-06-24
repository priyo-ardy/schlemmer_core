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
        Schema::create('projects', function (Blueprint $table) {
            // 1. Primary & Unique Identifiers
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique()->comment('Nomor Project internal atau nomor RFQ/Komersial');
            $table->string('name', 150)->comment('Nama Project, misal: Project Plastic Injection Door Trim');

            // 2. Relasi ke Customer / OEM (Krusial untuk Audit IATF)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete()
                ->comment('Menghubungkan ke master customer/OEM terkait');

            // 3. Atribut Manufaktur Otomotif (IATF Ready)
            $table->string('vehicle_model', 100)->nullable()->comment('Model mobil/motor, misal: Vios 2010, Yaris 2024');
            $table->string('main_part_number', 100)->nullable()->comment('Nomor part utama dari OEM/Customer');
            $table->string('main_part_name', 150)->nullable()->comment('Nama komponen utama');

            // 4. Tracking Fase APQP & Status (PFMEA Ready)
            // Catatan: PFMEA wajib di-generate pada Phase 3 (Process Design)
            $table->string('apqp_phase', 50)->default('phase_1')->nullable()
                ->comment('phase_1: Planning, phase_2: Product Design, phase_3: Process Design (PFMEA), phase_4: Validation, phase_5: Production');

            $table->string('status', 50)->default('development')->nullable()
                ->comment('development, mass_pro (SOP), hold, eop (End of Production)');

            // 5. Critical Date Milestones (Sangat sering dikejar Auditor IATF)
            $table->date('kick_off_date')->nullable();
            $table->date('target_proto_date')->nullable()->comment('Target sampel prototype');
            $table->date('target_ppap_date')->nullable()->comment('Target submission dokumen PPAP');
            $table->date('target_sop_date')->nullable()->comment('Target Start of Production (Kunci utama penentuan timeline)');

            // 6. Tata Kelola Data & Kerahasiaan (Future-Proof Security)
            $table->string('confidentiality_level', 30)->default('internal')
                ->comment('internal, customer_confidential, strictly_restricted');
            $table->unsignedInteger('revision')->default(0)->comment('Versi scope project jika ada perubahan teknis di tengah jalan');
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable()->comment('Catatan khusus atau spesifikasi scope tambahan');

            // 7. Audit Trails
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Proteksi mutlak agar tidak merusak data lembar kerja PFMEA yang sedang berjalan

            // 8. Indeks Optimasi Database
            $table->index(['status', 'apqp_phase']);
            $table->index('main_part_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
