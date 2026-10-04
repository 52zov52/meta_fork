<?php

use App\Models\Planet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * statuses:
     * draft - Черновик
     * scheduled - Запланировано
     * started - Начато
     * completed - Завершено
     * canceled - Отменено
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->date('date_start');
            $table->date('date_end');
            $table->integer('score');
            $table->string('status', 20)->default('scheduled');
            $table->foreignIdFor(Planet::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('photo_path', 2048)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
