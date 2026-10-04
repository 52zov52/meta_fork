<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Переименовывает institution_type → institution_id
     * Делает institution nullable
     * Добавляет academy_id и education_level
     * Добавляет foreign key с RESTRICT
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->renameColumn('institution_type', 'institution_id');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->string('institution')->nullable()->change();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('academy_id')->nullable()->after('institution')->constrained('academies')->onDelete('set null');
            $table->foreignId('education_level_id')->nullable()->after('academy_id')->constrained('education_levels')->onDelete('set null');
        });

        DB::statement('ALTER TABLE members MODIFY institution_id bigint unsigned NOT NULL');
        DB::statement('
            ALTER TABLE members
            ADD CONSTRAINT members_institution_id_foreign
            FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE RESTRICT
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE members DROP FOREIGN KEY members_institution_id_foreign');

        // up() разрешил NULL; заменяем их на пустую строку перед применением ограничения NOT NULL
        DB::table('members')->whereNull('institution')->update(['institution' => '']);

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropForeign(['education_level_id']);
            $table->dropColumn(['academy_id', 'education_level_id']);
            $table->renameColumn('institution_id', 'institution_type');
            $table->string('institution')->nullable(false)->change();
        });
    }
};
