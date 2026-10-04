<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_blackout_settings', function (Blueprint $table) {
            $table->id();
            $table->string('winter_start', 5);
            $table->string('winter_end', 5);
            $table->string('summer_start', 5);
            $table->string('summer_end', 5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_blackout_settings');
    }
};
