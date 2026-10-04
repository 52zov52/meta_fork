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
        Schema::table('portfolio', function (Blueprint $table) {
            $table->string('path_to_file', 2048)->nullable(true)->change();
            $table->string('original_name')->nullable(true)->change();
            $table->string('extension')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio', function (Blueprint $table) {
            $table->string('path_to_file', 2048)->nullable(false)->change();
            $table->string('original_name')->nullable(false)->change();
            $table->string('extension')->nullable(false)->change();
        });
    }
};
