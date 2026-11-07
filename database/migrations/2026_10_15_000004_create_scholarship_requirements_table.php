<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_program_id')->constrained('scholarship_programs')->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('allowed_types', 100)->default('pdf,jpg,jpeg,png');
            $table->unsignedInteger('max_size_kb')->default(5120);
            $table->timestamps();
            $table->index('scholarship_program_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_requirements');
    }
};
