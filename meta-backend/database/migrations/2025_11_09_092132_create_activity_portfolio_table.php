<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_portfolio', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portfolio_id')
                ->constrained('portfolio')
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['portfolio_id', 'activity_id']);
            $table->index(['activity_id', 'portfolio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_portfolio');
    }
};
