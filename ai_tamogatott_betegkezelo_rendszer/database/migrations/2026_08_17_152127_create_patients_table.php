<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->integer('taj_number')->unique();
            $table->string('name');
            $table->string('birth_date');
            $table->string('birth_place');
            $table->string('gender');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('address');
            $table->string('status');
            $table->boolean('is_urgent')->default(false);
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'status_changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
