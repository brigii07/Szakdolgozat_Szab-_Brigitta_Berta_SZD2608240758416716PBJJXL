<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('action');
            $table->timestamp('created_at')->useCurrent();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
