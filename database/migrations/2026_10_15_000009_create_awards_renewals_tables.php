<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_awards', function (Blueprint $table) {
            $table->id();
            $table->string('award_no', 30)->unique();
            $table->foreignId('scholarship_application_id')->unique()->constrained('scholarship_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')->constrained('scholarship_programs')->cascadeOnDelete();
            $table->text('benefits')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('recipient_status', 30)->default('active');
            $table->text('remarks')->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'recipient_status']);
        });

        Schema::create('renewal_applications', function (Blueprint $table) {
            $table->id();
            $table->string('renewal_no', 30)->unique();
            $table->foreignId('scholarship_award_id')->constrained('scholarship_awards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->decimal('gpa', 4, 2)->nullable();
            $table->text('statement')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['scholarship_award_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_applications');
        Schema::dropIfExists('scholarship_awards');
    }
};
