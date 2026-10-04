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
        // Add column as nullable (temporarily)
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()
                ->after('planet_id')
                ->constrained('activity_categories')
                ->cascadeOnDelete();
        });

        // Check if categories exist
        $firstCategory = DB::table('activity_categories')->first();
        if ($firstCategory) {
            $firstCategoryId = $firstCategory->id;

            // For production
            DB::table('activities')->update([
                'category_id' => $firstCategoryId,
            ]);
        }

        // Make column NOT NULL
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
