<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('care_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 30)->default('NO_INFORMATION');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->string('closing_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['patient_id', 'status']);
            $table->index(['status', 'status_changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_episodes');
    }
};
