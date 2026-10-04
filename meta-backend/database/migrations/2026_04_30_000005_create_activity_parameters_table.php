<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('activity_type', 50)->nullable();
            $table->boolean('has_places')->default(false);
            $table->boolean('has_implementation')->nullable();
            $table->boolean('is_leader')->default(false);
            // 0–2 ID соседних по NUO уровней обучения (целевые уровни активности).
            // NULL/[] = равные баллы для всех (KSU=1.00).
            $table->json('target_education_level_ids')->nullable();
            $table->timestamps();

            $table->unique('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_parameters');
    }
};
