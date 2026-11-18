<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercises')) {
            Schema::create('exercises', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 120);
                $table->string('type', 20)->default('choice'); // choice | spelling
                $table->json('items');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exercise_attempts')) {
            Schema::create('exercise_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('score');
                $table->unsignedSmallInteger('total');
                $table->unsignedTinyInteger('stars');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('quiz_attempts') && ! Schema::hasColumn('quiz_attempts', 'mode')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->string('mode', 20)->default('choice');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_attempts');
        Schema::dropIfExists('exercises');
        if (Schema::hasColumn('quiz_attempts', 'mode')) {
            Schema::table('quiz_attempts', fn (Blueprint $t) => $t->dropColumn('mode'));
        }
    }
};

