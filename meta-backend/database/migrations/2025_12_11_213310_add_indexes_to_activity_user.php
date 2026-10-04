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
            $table->index(
                ['user_id', 'is_confirmed', 'updated_at', 'activity_id'],
                'idx_realtime_scoring'
            );

            $table->index('activity_id');

            $table->index(['user_id', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            $table->dropIndex('idx_realtime_scoring');
            $table->dropIndex(['activity_id']);
            $table->dropIndex(['user_id', 'activity_id']);
        });
    }
};
