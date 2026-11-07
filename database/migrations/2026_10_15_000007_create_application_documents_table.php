<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications')->cascadeOnDelete();
            $table->foreignId('scholarship_requirement_id')->nullable()->constrained('scholarship_requirements')->nullOnDelete();
            $table->string('document_name', 200);
            $table->string('file_path', 500);
            $table->string('file_type', 20)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('verification_status', 30)->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['scholarship_application_id', 'verification_status'], 'app_docs_appid_verifstatus_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
