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
        Schema::create('activity_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('education_level_id')->constrained('education_levels')->cascadeOnDelete();
            $table->string('place', 20);
            $table->unsignedInteger('base_score');
            $table->unsignedTinyInteger('nuo_value');
            $table->unsignedTinyInteger('uta_level');
            $table->tinyInteger('delta');
            $table->decimal('ksu_coefficient', 3, 2);
            $table->unsignedInteger('final_score');
            $table->timestamps();

            $table->unique(['activity_id', 'education_level_id', 'place']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_scores');
    }
};
