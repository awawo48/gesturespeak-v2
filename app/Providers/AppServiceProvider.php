<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.gs');
        Paginator::defaultSimpleView('vendor.pagination.gs');

        // Auto-ensure teacher exercises + quiz mode column exist even before manual migrate
        try {
            if (Schema::hasTable('users') && Schema::hasTable('quiz_attempts')
                && (! Schema::hasTable('exercises') || ! Schema::hasTable('exercise_attempts') || ! Schema::hasColumn('quiz_attempts', 'mode'))) {
                (require database_path('migrations/2026_11_14_000001_create_exercises_tables.php'))->up();
            }
        } catch (\Throwable) {
            // Ignore during initial install
        }

        // Auto-ensure teacher/parent differentiation columns exist even before manual `php artisan migrate`
        try {
            if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'status')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('status', 20)->default('approved');
                    $table->timestamp('approved_at')->nullable();
                });
            }

            if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'teacher_note')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->text('teacher_note')->nullable();
                    $table->foreignId('teacher_note_by')->nullable()->constrained('users')->nullOnDelete();
                    $table->timestamp('teacher_note_updated_at')->nullable();
                    $table->foreignId('assigned_category_id')->nullable()->constrained('categories')->nullOnDelete();
                });
            }
        } catch (\Throwable) {
            // Ignore during initial install before tables exist
        }
    }
}
