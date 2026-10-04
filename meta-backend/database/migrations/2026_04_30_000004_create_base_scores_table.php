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
        Schema::create('base_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('activity_categories')->cascadeOnDelete();
            $table->string('activity_type', 50)->nullable();
            $table->boolean('has_places')->default(false);
            $table->boolean('has_implementation')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->unsignedInteger('first_place')->nullable();
            $table->unsignedInteger('second_place')->nullable();
            $table->unsignedInteger('third_place')->nullable();
            $table->unsignedInteger('participation');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'activity_type']);
            $table->index(['category_id', 'has_places', 'has_implementation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_scores');
    }
};
