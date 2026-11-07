<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->cascadeOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 30)->default('manual_review');
            $table->text('reasons')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->index('scholarship_application_id');
        });

        Schema::create('application_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('recommendation', 40)->default('pending');
            $table->text('evaluation_notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->index('scholarship_application_id');
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index('scholarship_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('application_evaluations');
        Schema::dropIfExists('eligibility_results');
    }
};
