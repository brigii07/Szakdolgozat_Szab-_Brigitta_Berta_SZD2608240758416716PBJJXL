<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('episode_id')->nullable()->constrained('care_episodes')->onDelete('cascade');
            $table->string('type');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('incapacity_reason')->nullable();
            $table->string('file_path');
            $table->text('content')->nullable();
            $table->boolean('ai_drafted')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
