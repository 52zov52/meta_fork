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
        Schema::table('activity_user', function (Blueprint $table) {
            $table->string('place', 20)->nullable()->after('level');
            $table->unsignedInteger('earned_score')->nullable()->after('place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            $table->dropColumn(['place', 'earned_score']);
        });
    }
};
