<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher vs Parent differentiation:
 * - users.status / approved_at : teachers must be approved by Admin.
 * - students.teacher_note*     : teacher feedback visible to parents.
 * - students.assigned_category_id : weekly "homework" module set by teacher.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                // Existing accounts default to approved so nobody gets locked out
                $table->string('status', 20)->default('approved')->after('role'); // pending | approved | rejected
                $table->timestamp('approved_at')->nullable()->after('status');
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
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_note_by');
            $table->dropConstrainedForeignId('assigned_category_id');
            $table->dropColumn(['teacher_note', 'teacher_note_updated_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};

