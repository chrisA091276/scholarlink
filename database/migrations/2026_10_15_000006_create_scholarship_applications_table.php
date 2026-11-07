<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')->constrained('scholarship_programs')->cascadeOnDelete();
            $table->string('academic_year', 20)->default('2026-2027');
            $table->string('status', 40)->default('draft');
            $table->string('enrollment_status', 30)->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->text('statement')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_remarks')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'scholarship_program_id', 'academic_year'], 'uniq_app_user_schol_year');
            $table->index(['status', 'scholarship_program_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
