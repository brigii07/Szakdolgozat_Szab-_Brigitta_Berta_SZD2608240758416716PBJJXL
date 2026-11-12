<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('symptom_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('symptom_id')->constrained('symptoms')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('episode_id')->nullable()->constrained('care_episodes')->onDelete('cascade');
            $table->string('severity');
            $table->string('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['episode_id', 'symptom_id']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('symptom_patient');
    }
};
