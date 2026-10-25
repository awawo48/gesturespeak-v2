<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Core learning tables for GestureSpeak.
     */
    public function up(): void
    {
        // Child profiles (created by a parent or teacher)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 12)->unique(); // e.g. ADAM-4821, shared between parent & teacher
            $table->string('name');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('pin');                        // hashed 4-digit PIN for child login
            $table->string('avatar', 20)->default('lion');
            $table->unsignedInteger('stars')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        // Many adults (parent + teacher) <-> many children
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 20); // parent | teacher
            $table->timestamps();
            $table->unique(['student_id', 'user_id']);
        });

        // Learning categories: Huruf, Nombor, Salam, Keluarga, Perasaan, Soalan, Perkataan
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_en');
            $table->string('description')->nullable();
            $table->string('icon', 16)->nullable();
            $table->string('color', 20)->default('#7c5cff');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Individual BIM signs
        Schema::create('signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('label');                 // AI label, e.g. "A", "5", "BUKU"
            $table->string('word');                  // Display word (Malay)
            $table->string('meaning_en')->nullable();
            $table->string('video')->nullable();     // path under public/
            $table->string('image')->nullable();     // path under public/
            $table->text('instructions')->nullable();
            $table->boolean('is_static')->default(true); // static = AI camera practice supported
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'label']);
        });

        // Per-child progress for each sign
        Schema::create('sign_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sign_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->unsignedInteger('practice_count')->default(0);
            $table->decimal('best_confidence', 5, 2)->default(0);
            $table->boolean('mastered')->default(false);
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'sign_id']);
        });

        // Quiz results
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total');
            $table->unsignedTinyInteger('stars');
            $table->timestamps();
        });

        // Hand-landmark samples recorded by the team to train the AI
        Schema::create('gesture_samples', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->json('features');                // 63 normalised numbers
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gesture_samples');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('sign_progress');
        Schema::dropIfExists('signs');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('student_guardians');
        Schema::dropIfExists('students');
    }
};
