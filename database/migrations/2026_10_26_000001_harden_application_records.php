<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_profiles', fn (Blueprint $table) => $table->string('student_id_no', 50)->nullable()->change());
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('last_saved_step')->default(1);
            $table->timestamp('declaration_accepted_at')->nullable();
        });
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('storage_disk', 20)->default('local');
            $table->softDeletes();
        });
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reviews');
        Schema::table('application_documents', fn (Blueprint $table) => $table->dropColumn(['storage_disk', 'deleted_at']));
        Schema::table('scholarship_applications', fn (Blueprint $table) => $table->dropColumn(['last_saved_step', 'declaration_accepted_at']));
        // Student numbers remain nullable: restoring NOT NULL would destroy valid registrations.
    }
};
