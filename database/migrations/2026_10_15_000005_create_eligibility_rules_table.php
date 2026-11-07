<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_program_id')->constrained('scholarship_programs')->cascadeOnDelete();
            $table->string('rule_key', 60);
            $table->string('rule_label', 200);
            $table->string('rule_type', 30)->default('text');
            $table->string('operator', 20)->default('=');
            $table->text('rule_value');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['scholarship_program_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_rules');
    }
};
