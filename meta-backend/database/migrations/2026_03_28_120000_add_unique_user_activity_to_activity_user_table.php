<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'activity_id']);
            $table->unique(['user_id', 'activity_id'], 'activity_user_user_id_activity_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            $table->dropUnique('activity_user_user_id_activity_id_unique');
            $table->index(['user_id', 'activity_id']);
        });
    }
};
