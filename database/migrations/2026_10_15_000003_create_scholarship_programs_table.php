<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Scholarship ID');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->default('General');
            $table->text('benefits')->nullable();
            $table->text('eligibility_criteria')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('deadline')->nullable();
            $table->unsignedInteger('slots')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'deadline']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_programs');
    }
};
