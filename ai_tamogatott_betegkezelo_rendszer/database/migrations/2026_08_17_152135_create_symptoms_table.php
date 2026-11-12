<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('symptoms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('detail_type')->nullable();
            $table->string('detail_label')->nullable();
            $table->string('detail_unit', 20)->nullable();
            $table->decimal('detail_min', 6, 2)->nullable();
            $table->decimal('detail_max', 6, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptoms');
    }
};
