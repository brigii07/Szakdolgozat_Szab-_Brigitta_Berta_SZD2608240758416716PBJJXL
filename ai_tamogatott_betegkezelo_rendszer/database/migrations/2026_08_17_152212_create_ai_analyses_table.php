<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained('care_episodes')->cascadeOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->json('suggestions');
            $table->json('input_snapshot')->nullable();
            $table->string('model', 100)->nullable();
            $table->string('selected_diagnosis')->nullable();
            $table->unsignedTinyInteger('selected_rank')->nullable();
            $table->text('summary')->nullable();
            $table->text('recommendation')->nullable();
            $table->integer('confidence')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
