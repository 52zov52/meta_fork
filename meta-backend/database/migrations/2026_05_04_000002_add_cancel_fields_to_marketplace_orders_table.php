<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE marketplace_orders MODIFY status ENUM('pending','issued','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('issued_by');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('marketplace_orders')
            ->where('status', 'cancelled')
            ->update(['status' => 'pending']);

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by']);
        });

        DB::statement("ALTER TABLE marketplace_orders MODIFY status ENUM('pending','issued') NOT NULL DEFAULT 'pending'");
    }
};
