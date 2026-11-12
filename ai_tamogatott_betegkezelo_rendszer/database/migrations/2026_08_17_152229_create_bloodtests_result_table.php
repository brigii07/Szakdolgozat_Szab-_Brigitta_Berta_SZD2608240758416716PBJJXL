<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bloodtests_result', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloodtest_id')->constrained('bloodtests')->onDelete('cascade');
            $table->string('parameter_name');
            $table->string('value');
            $table->string('unit');
            $table->string('reference_min')->nullable();
            $table->string('reference_max')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloodtests_result');
    }
};
