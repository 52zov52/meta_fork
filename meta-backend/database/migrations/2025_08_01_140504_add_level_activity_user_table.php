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
            $table->float('level')->default(0.4)->after('activity_id');
        });

        /* const LEVEL_CONFIRMED_1ST_PLACE = 1; */
        /* const LEVEL_CONFIRMED_2ND_PLACE = 0.8; */
        /* const LEVEL_CONFIRMED_3RD_PLACE = 0.6; */
        /* const LEVEL_CONFIRMED_PARTICIPANT = 0.4; */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
