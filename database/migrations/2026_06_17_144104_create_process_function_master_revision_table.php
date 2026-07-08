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
        Schema::create('process_revision_headers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('header_id')->constrained('process_functions')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('name', 150);
            $table->text('remark')->nullable();
            $table->text('change_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['header_id', 'revision']);
        });

        Schema::create('process_revision_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('revision_header_id')->constrained('process_revision_headers')->cascadeOnDelete();
            $table->uuid('detail_uuid');
            $table->unsignedInteger('order');
            $table->string('previous_problem')->nullable();
            $table->string('requirements');
            $table->string('potential_failure_mode');
            $table->string('potential_effect_of_failure');
            $table->string('potential_cause_of_failure');
            $table->string('controls_prevention');
            $table->string('controls_detection');

            $table->timestamps();

            $table->index(['revision_header_id']);
            $table->index(['detail_uuid']);
        });

        Schema::create('process_revision_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('header_id')
                ->constrained('process_functions')
                ->cascadeOnDelete();

            $table->unsignedInteger('revision');

            $table->enum('action', [
                'CREATE',
                'UPDATE',
                'APPROVE',
                'REJECT',
                'ROLLBACK',
            ]);

            $table->text('change_reason')->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamps();

            $table->index(['header_id', 'revision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('process_revision_logs');
        Schema::dropIfExists('process_revision_details');
        Schema::dropIfExists('process_revision_headers');
        Schema::enableForeignKeyConstraints();
    }
};
