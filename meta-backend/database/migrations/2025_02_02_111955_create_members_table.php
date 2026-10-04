<?php

use App\Models\Planet;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->integer('gender')->default(0);
            $table->date('birthday')->nullable();
            $table->foreignIdFor(Region::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Planet::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('institution_type');
            $table->string('institution');
            $table->integer('course')->nullable();
            $table->string('student_group', 10)->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
