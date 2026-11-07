<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scholarship_awards', function (Blueprint $table) {
            $table->text('status_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->unsignedInteger('status_version')->default(0);
        });
        Schema::create('scholarship_appeals', function (Blueprint $table) {
            $table->id();
            $table->string('appeal_no', 40)->unique();
            $table->foreignId('scholarship_award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('award_status_version');
            $table->string('award_status', 30);
            $table->text('status_reason')->nullable();
            $table->text('statement');
            $table->string('status', 30)->default('submitted');
            // NULL permits multiple historical decisions; 1 reserves the open appeal.
            $table->unsignedTinyInteger('pending_slot')->nullable()->default(1);
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['scholarship_award_id', 'pending_slot'], 'appeals_one_pending_per_award');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_appeals');
        Schema::table('scholarship_awards', fn (Blueprint $table) => $table->dropColumn(['status_reason', 'status_changed_at', 'status_version']));
    }
};
